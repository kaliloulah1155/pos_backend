<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Produit\StoreRequest;
use App\Models\Produit;
use App\Models\Categorie;
use App\Services\ImageService;
use App\Services\AmountFormatService;
use Auth;
use Carbon\Carbon;
use DB;
use App\Models\User;

class ProduitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {

            $produits = DB::table('produits')
            ->select(
                'produits.id',
                'produits.libelle',
                'produits.code',
                'produits.image',
                'produits.buying_price',
                'produits.selling_price',
                'produits.fournisseur_id',
                'produits.quantite',
                'categories.libelle as category_name'
            )
            ->leftJoin('categorie_produit', 'produits.id', '=', 'categorie_produit.produit_id')
            ->leftJoin('categories', 'categorie_produit.categorie_id', '=', 'categories.id')
            ->whereNull('produits.deleted_at') // Add this line to filter out soft-deleted records
            ->get();

            // Map each products
           $mappedProduits = $produits->groupBy('id')->map(function ($produitGroup) {
            $firstProduit = $produitGroup->first();
           
            $user = User::find($firstProduit->fournisseur_id);
                return [
                    'id' => $firstProduit->id,
                    'libelle' => $firstProduit->libelle,
                    'code' => $firstProduit->code,
                    'fournisseur_id' => $user ? $user->id: null,
                    'fournisseur' => $user ? $user->nom.' '.$user->prenoms : "NEANT",
                    'categories' => $produitGroup->pluck('category_name')->all(),
                    'image' => $firstProduit->image ? env('IMAGE_PATH_PRODUITS').$firstProduit->image : null,
                    'buying_price' => (new AmountFormatService)->formatAmount($firstProduit->buying_price) . ' F CFA',
                    'selling_price' => (new AmountFormatService)->formatAmount($firstProduit->selling_price) . ' F CFA',
                    'quantite' => $firstProduit->quantite,
                ];
           });
            // Return the paginated data with additional information
            return response()->json([
                'data' => $mappedProduits->values()->all(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.index',
            ]);

        }
    }
    
     /**
     * Display a listing of stock the resource.
     *
     * @return \Illuminate\Http\Response
     */
        public function stocks(){
        try {
        
         $produits = DB::table('produits')
            ->select(
                'produits.id',
                'produits.libelle',
                'produits.code',
                'produits.image',
                'produits.buying_price',
                'produits.selling_price',
                'produits.fournisseur_id',
                'produits.quantite',
                'categories.libelle as category_name'
            )
            ->leftJoin('categorie_produit', 'produits.id', '=', 'categorie_produit.produit_id')
            ->leftJoin('categories', 'categorie_produit.categorie_id', '=', 'categories.id')
            ->whereNull('produits.deleted_at') // Add this line to filter out soft-deleted records
            ->get();

            // Map each products
           $mappedProduits = $produits->groupBy('id')->map(function ($produitGroup) {
            $firstProduit = $produitGroup->first();
            
            $stockStatus ="Stock";
            if((intval($firstProduit->quantite) <= 0))  $stockStatus ="Rupture";

            $user = User::find($firstProduit->fournisseur_id);
            return [
                'id' => $firstProduit->id,
                'libelle' => $firstProduit->libelle,
                'code' => $firstProduit->code,
                'fournisseur' => $user ? $user->nom.' '.$user->prenoms : "NEANT",
                'categories' => $produitGroup->pluck('category_name')->all(),
                'image' => $firstProduit->image ? env('IMAGE_PATH_PRODUITS').$firstProduit->image : null,
                'val_buying_price' =>intval($firstProduit->buying_price),
                'buying_price' => (new AmountFormatService)->formatAmount($firstProduit->buying_price) . ' F CFA',
                'selling_price' => (new AmountFormatService)->formatAmount($firstProduit->selling_price) . ' F CFA',
                'val_selling_price' =>intval($firstProduit->selling_price),
                 'stock' => $stockStatus,
                'quantite' => $firstProduit->quantite,
            ];
        });
        
        // Return the paginated data with additional information
        return response()->json([
            'data' => $mappedProduits->values()->all(),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'message' => 'Something went wrong in ProduitController.stocks',
        ]);
    }
}


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        
        try {
            
            $code = Produit::formatChaine($request->libelle);
            
            $existingProduit = Produit::where('code', $code)->first();
             while ($existingProduit) {
                $code = Produit::formatChaine(Produit::generateRandomAlphaCode());
                $existingProduit = Produit::where('code', $code)->first();
            }
            
            $produit = Produit::create([
                'libelle' => $request->libelle,
                'code' => $code,
                'buying_price' =>  $request->buying_price ?? 0,
                'selling_price' =>  $request->selling_price ?? 0,
                'quantite' =>  $request->quantite ?? 0,
                'fournisseur_id' =>  $request->fournisseur_id ?? null,
                'created_user' => Auth::id(),
            ]);
            $produit->categories()->sync(json_decode($request->input('categories')));
            if ($request->hasFile('image')) {
                (new ImageService)->updateImage($produit, $request, '/images/produits/', 'update');
                $produit->save();
            }
            
            return response()->json($produit, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.store',
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $id)
    {
        
       // return response()->json(["data"=>json_decode($request->input('categories')),"id"=>$id]);
        try {

            $produit = Produit::find($id);
            if (!$produit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produit avec l\'id ' . $id . ' n\'existe pas!',
                ], 200);
            }

            if ($request->hasFile('image')) {

                (new ImageService)->updateImage($produit, $request, '/images/produits/', 'update');
            }
            $produit->libelle = $request->libelle ?? $produit->libelle;
            $produit->buying_price =  intval($request->buying_price) ?? $produit->buying_price;
            $produit->selling_price =  intval($request->selling_price) ?? $produit->selling_price;
            $produit->quantite =  intval($request->quantite) ?? $produit->quantite;
            $produit->fournisseur_id =  intval($request->fournisseur_id) ?? $produit->fournisseur_id;
            $produit->updated_user = Auth::id();
   
            $produit->categories()->sync(json_decode($request->input('categories')));  //maj categories
            $produit->save();

        
            return response()->json([
                'success' => true,
                'message' => 'Produit avec l\'id ' . $id . ' a été mis à jour!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.update',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {

        try {
            $record = Produit::where('id', $id)->get();
            $current = Carbon::now();

           
                if (count($record) > 0) {
                    Produit::where('id', $id)->update([
                        'deleted_at' => $current,
                        'deleted_user' => Auth::id(),
                    ]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Produit avec l\'id ' . $id . ' a été supprimé!']
                        , 201);
                } else {
                    return response()->json([
                        'result' => false,
                        "message" => "Ce Produit n'existe pas",
                    ]);
                }
            

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.destroy',
            ]);
        }

    }

    public function delete(int $id)
    {
        try {
           $record = Produit::withTrashed()->where('id', $id)->get();

            if (count($record)) {
                DB::table('produits')->where('id', $id)->delete();
                 DB::table('categorie_produit')->where('produit_id', $id)->delete();
               // $record->categories()->detach();
                return response()->json([
                    'success' => true,
                    'message' => 'Produit avec l\'id ' . $id . ' a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "Ce Produit n'existe pas",
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.delete',
            ]);
        }
    }
    
    public function pos_categorie(){
        try {
            
             $categories = Categorie::where("statut",1)->get();
             
             // Map each user to include profile name
            $mappedCategories = $categories->map(function ($categorie) {
                return [
                    'id' => $categorie->id,
                    'libelle' => $categorie->libelle,
                ];
            });

            // Return the paginated data with additional information
            return response()->json([
                'data' => $mappedCategories,
            ]);
            
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.pos_categorie',
            ]);
        }
    }
    
    public function pos_produit_by_categorie($categoryId){
        try {
            
        

            $products = Produit::with('categories')
                ->whereHas('categories', function ($query) use ($categoryId) {
                    $query->where('categories.id', intval($categoryId));
                })
                ->get()
                ->map(function ($product) {
                    
                    $stockStatus = "Stock";
                    if ($product->quantite <= 0) {
                        $stockStatus = "Rupture";
                    }
                    $product->stock = $stockStatus; // Changer le nom de la clé de stock
                    
                    $product->image = $product->image ? env('IMAGE_PATH_PRODUITS').$product->image : null;
                    $product->buying_price = (new AmountFormatService)->formatAmount($product->buying_price) . ' F CFA';
                    $product->selling_price = (new AmountFormatService)->formatAmount($product->selling_price) . ' F CFA';
                    
                    unset($product->categories); // Supprimer la clé quantite si nécessaire
                    
                    return $product;
                });
                
             return response()->json([
                'data' => $products,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.pos_produit_by_categorie',
            ]);
        }
    }
}
