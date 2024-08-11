<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ItemRandom;
use Cart;
use App\Models\Produit;
use App\Models\Pos;
use App\Models\PosCartItem;
use Validator;
use Illuminate\Support\Facades\DB;
use PDF;

class TestCartController extends Controller
{
        public function content()
        {
            $userId = auth()->user()->id;
            $cartItems = Cart::session($userId)->getContent();
            $total = Cart::session($userId)->getTotal();
            $totalProducts = $cartItems->count();
            $cartTotalQuantity = Cart::session($userId)->getTotalQuantity();
            
            foreach ($cartItems as $item) {
                $product = Produit::find($item->id);
                
                // Mettre à jour l'image du produit dans l'élément du panier
                $item['image'] = $product->image ? env('IMAGE_PATH_PRODUITS').$product->image : null;
            }
            
            return response()->json([
                'cartItems' => $cartItems,
                'total' => $total,
                'totalProducts' => $totalProducts,
                'cartTotalQuantity'=>$cartTotalQuantity
            ]);
        }


    public function addItem($productId)
    {
        
        
         $userId = auth()->user()->id;
        $product = Produit::findOrFail($productId);
        if(intval($product->quantite) == 0 ){
             return response()->json([
                               'message' => 'Stock insuffisant'
                              ]);
        }
        
                 // Récupérer le contenu du panier
            $cartItems = Cart::session($userId)->getContent();

             foreach ($cartItems as $k1 => $v1) {
                 if($k1 == strval($productId)){
                     if( intval($v1["quantity"]) > intval($product->quantite)-1 ){
                          return response()->json([
                               'message' => 'La quantité du produit est superieur au stock'
                              ]);
                     }
                 }
             }
          
           
            
             // Sinon, ajoutez le produit au panier
            Cart::session($userId)->add([
                'id' => $product->id,
                'name' => $product->libelle,
                'quantity' => 1,
                'price' => intval($product->selling_price),
                'image'=>$product->image ? env('IMAGE_PATH_PRODUITS').$product->image : null
            ]);
                
         return response()->json([
             'message' => 'Produit ajouté au panier'
             ]);

    }
    public function clearCart(){
         $userId = auth()->user()->id;
         $cartCollection = Cart::session($userId)->getContent();
         Cart::session($userId)->clear();
          return response()->json([
            "total"=>Cart::getTotal(),
            "cart"=>$cartCollection
            ]);
    }
    
    public function increaseQuantity($productId)
    {
             
           $userId = auth()->user()->id;
        $product = Produit::findOrFail($productId);
         if(intval($product->quantite) == 0 ){
             return response()->json([
                               'message' => 'Stock insuffisant'
                              ]);
        }
        
                 // Récupérer le contenu du panier
            $cartItems = Cart::session($userId)->getContent();
            
            foreach ($cartItems as $k1 => $v1) {
                 if($k1 == strval($productId)){
                     if( intval($v1["quantity"]) > intval($product->quantite)-1 ){
                          return response()->json([
                              'error'=>true,
                              'message' => 'La quantité du produit est superieur au stock'
                              ]);
                     }
                 }
             }
             // Sinon, ajoutez le produit au panier
            Cart::session($userId)->add([
                'id' => $product->id,
                'name' => $product->libelle,
                'quantity' => 1,
                'price' => intval($product->selling_price),
                'image'=>$product->image ? env('IMAGE_PATH_PRODUITS').$product->image : null
            ]);
                
         return response()->json(['message' => 'Produit incrementé']);
    }

    
public function decreaseQuantity($productId)
{
    $userId = auth()->user()->id;

    // Récupérer le contenu du panier
    $cartItems = Cart::session($userId)->getContent();

    // Parcourir les éléments du panier pour trouver le produit correspondant
    foreach ($cartItems as $cartItem) {
           
        if ($cartItem->id == $productId) {
            
           
            // Supprimer l'élément du panier avec la quantité actuelle
            Cart::session($userId)->remove($cartItem->id);
             if( ($cartItem->quantity - 1) == 0){
                 Cart::session($userId)->remove($cartItem->id);
                 return response()->json(['message' => 'Produit supprimé du panier']);
             }

            // Ajouter le produit de nouveau avec une quantité diminuée
            Cart::session($userId)->add([
                'id' => $cartItem->id,
                'name' => $cartItem->name,
                'quantity' => $cartItem->quantity - 1,
                'price' => $cartItem->price,
                'attributes' => $cartItem->attributes,
            ]);

            return response()->json(['message' => 'Produit decrementé']);
        }
    }

    // Si le produit n'est pas dans le panier, retourner un message d'erreur
    return response()->json(['message' => 'Produit introuvable'], 404);
}

    public function removeItem($productId){
        
        $userId = auth()->user()->id;
        // Récupérer le contenu du panier
        $cartItems = Cart::session($userId)->getContent();
        // Parcourir les éléments du panier pour trouver le produit correspondant
        foreach ($cartItems as $cartItem) {
               
            if ($cartItem->id == $productId) {
                
                // Supprimer l'élément du panier avec la quantité actuelle
                Cart::session($userId)->remove($cartItem->id);
                 return response()->json(['message' => 'Produit supprimé du panier']);
               
            }
        }
        // Si le produit n'est pas dans le panier, retourner un message d'erreur
        return response()->json(['message' => 'Produit introuvable'], 404);
        
    }

    public function addOrder(Request $request){
        
         $validator = Validator::make($request->all(), [
                'client_id' => 'required|integer',
                'tva' => 'required|integer',
                'remise' => 'required|integer',
                'espece' => 'required|integer',
                'qte_total' => 'required|integer',
                'paid_method_id' => 'required|integer'
            ]);
            
         // Vérifier les erreurs de validation
        if ($validator->fails()) {
            return response()->json(["error"=>true, "info"=>"Champ requis manquant", "data"=> $validator->errors()]);
        }
        $userId = auth()->user()->id;
        $data_pos=[];
        $data_pos_cart_items=[];
        
        $data_pos["created_user"] = intval($userId);
        $data_pos["client_id"] = intval($request->client_id);
        $data_pos["tva"] = intval($request->tva);
        $data_pos["remise"] = intval($request->remise);
        $data_pos["espece"] = intval($request->espece);
        $data_pos["monnaie"] = intval($request->monnaie);
        $data_pos["qte_total"] = intval($request->qte_total);
        $data_pos["paid_method_id"] = intval($request->paid_method_id);
        $data_pos["print_status"] = 0;
        $data_pos["status"] = 1;
        $data_pos["transaction_id"] = strtoupper(uniqid());
        
        $pos = Pos::updateOrCreate(
                [
                    'transaction_id' => strtoupper(uniqid()),
                ], $data_pos); 
        
        // Récupérer le contenu du panier
        $cartItems = Cart::session($userId)->getContent();
        
        if(count($cartItems)>0){
            
            foreach ($cartItems as $cartItem) {

                  $data_pos_cart_items[] = [
                        'pos_id' => $pos->id,
                        'item_id' => intval($cartItem->id),
                        'qte' => intval($cartItem->quantity),
                        'price' => intval($cartItem->price),
                        'price_by_qte' => intval($cartItem->price)*intval($cartItem->quantity),
                        'status' => 1,
                        'created_user'=>intval($userId)
                  ];
                  
                  //Mouvement du stock
                  $this->checkout(intval($cartItem->id),intval($cartItem->quantity));
            }
            
        }
        
       
        // Vérifiez si le tableau n'est pas vide avant d'insérer
        if (!empty($data_pos_cart_items)) {
            PosCartItem::insert($data_pos_cart_items);
        }
        
        
        //Vider le panier 
        $this->clearCart();
        
            
        return response()->json([
             'status'=>'success',
             'pos_id'=>$pos->id,
             'message' => 'Commande effectuée'
            ], 201);
    }
    
    public function checkout($id,$qte){
        
        DB::beginTransaction();

        try {
            
            $product = Produit::findOrFail($id);
            if (intval($product->quantite) <$qte) {
                
                return response()->json([
                    'status'=>'error',
                    'message' => 'Stock insuffisant pour le produit : ' . $product->name
                ], 201);
            }
            $product->quantite -= $qte;
            $product->save();
            
             DB::commit();
             
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
    
    //Generation du pdf à imprimer
      
      /**
     * @OA\Get(
     *     path="/printOrder/{id}",
     *     tags={"Commandes"},
     *      summary="Lien de l'imprimer du ticket | id=identifiant de la commande",
     *      description="Retourne le lien de l'imprimer",
     *      @OA\Parameter(
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *      @OA\Response(response=200,description="succès"),
     *      @OA\Response(response=401, description="Token expiré | Token invalide | Token absent "),
     *      @OA\Response(response=404, description="Ressource introuvable")  
     * ),
     */
    public function pdfOrder($id){
        
        try {
            
            $resPos = DB::select("CALL GetPos(?)",[$id]);
            $resPosItems = DB::select("CALL GetPosItem(?)",[$id]);
            
            
            $pdf = PDF::loadView('pdf.print_order', ['resPos'=>$resPos , 'resPosItems'=>$resPosItems])->setPaper([0, 0, 300, 400], 'portrait');
            $pdf->getDomPDF()->set_option("enable_php", true);
           return $pdf->stream('print_order'.date('Y-m-d_H-i-s').'.pdf');
           
    
       } catch (\Exception $e) {
           return response()->json([
               'error' => $e->getMessage(),
               'message' => 'Something went wrong in TestCartController.pdfOrder',
           ]);
       }
    }

    
   
    
    
}
