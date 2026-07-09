<?php

namespace App\Http\Controllers\API;

use App\Events\ProductScanned;
use App\Http\Controllers\Controller;
use App\Models\Entreprise;
use App\Models\Pos;
use App\Models\PosCartItem;
use App\Models\Produit;
use Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Validator;

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
            $item['image'] = $product && $product->image
                ? env('IMAGE_PATH_PRODUITS') . $product->image
                : null;
        }

        return response()->json([
            'cartItems'         => $cartItems,
            'total'             => $total,
            'totalProducts'     => $totalProducts,
            'cartTotalQuantity' => $cartTotalQuantity,
        ]);
    }

    /**
     * SCAN PRODUIT (code barre)
     */
    public function scan(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $userId = auth()->user()->id;
        $code   = trim($request->code);

        // ── Recherche par barcode EN PRIORITÉ, fallback sur code ────
        $product = Produit::where('barcode', $code)
                        ->orWhere('code', $code)
                        ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit introuvable',
            ], 404);
        }

        if (intval($product->quantite) <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant',
            ], 422);
        }

        $cartItems = Cart::session($userId)->getContent();
        foreach ($cartItems as $item) {
            if ($item->id == $product->id && $item->quantity >= $product->quantite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock dépassé',
                ], 422);
            }
        }

        Cart::session($userId)->add([
            'id'         => $product->id,
            'name'       => $product->libelle,
            'quantity'   => 1,
            'price'      => intval($product->selling_price),
            'attributes' => [
                'image'   => $product->image
                            ? env('IMAGE_PATH_PRODUITS') . $product->image
                            : null,
                'barcode' => $product->barcode,   // ← barcode au lieu de code
            ],
        ]);

        $cartTotal    = Cart::session($userId)->getTotal();
        $cartQuantity = Cart::session($userId)->getTotalQuantity();

        broadcast(new ProductScanned(
            cartItem: [
                'id'            => $product->id,
                'name'          => $product->libelle,
                'price'         => intval($product->selling_price),
                'quantity'      => 1,
                'image'         => $product->image
                                    ? env('IMAGE_PATH_PRODUITS') . $product->image
                                    : null,
                'cart_total'    => $cartTotal,
                'cart_quantity' => $cartQuantity,
            ],
            userId: $userId,
        ))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Produit ajouté',
            'product' => [
                'id'      => $product->id,
                'name'    => $product->libelle,
                'price'   => $product->selling_price,
                'image'   => $product->image
                            ? env('IMAGE_PATH_PRODUITS') . $product->image
                            : null,
                'barcode' => $product->barcode,   // ← retourné au mobile
            ],
        ]);
    }

    public function addItem($productId)
    {
        $userId  = auth()->user()->id;
        $product = Produit::findOrFail($productId);

        if (intval($product->quantite) == 0) {
            return response()->json(['message' => 'Stock insuffisant']);
        }

        $cartItems = Cart::session($userId)->getContent();

        foreach ($cartItems as $k1 => $v1) {
            if ($k1 == strval($productId)) {
                if (intval($v1["quantity"]) > intval($product->quantite) - 1) {
                    return response()->json([
                        'message' => 'La quantité du produit est superieur au stock',
                    ]);
                }
            }
        }

        Cart::session($userId)->add([
            'id'         => $product->id,
            'name'       => $product->libelle,
            'quantity'   => 1,
            'price'      => intval($product->selling_price),
            'attributes' => [
                'image' => $product->image
                    ? env('IMAGE_PATH_PRODUITS') . $product->image
                    : null,
            ],
        ]);

        return response()->json([
            'message' => 'Produit ajouté au panier',
        ]);
    }

    public function clearCart()
    {
        $userId          = auth()->user()->id;
        $cartCollection  = Cart::session($userId)->getContent();
        Cart::session($userId)->clear();

        return response()->json([
            "total" => Cart::getTotal(),
            "cart"  => $cartCollection,
        ]);
    }

    public function increaseQuantity($productId)
    {
        $userId  = auth()->user()->id;
        $product = Produit::findOrFail($productId);

        if (intval($product->quantite) == 0) {
            return response()->json(['message' => 'Stock insuffisant']);
        }

        $cartItems = Cart::session($userId)->getContent();

        foreach ($cartItems as $k1 => $v1) {
            if ($k1 == strval($productId)) {
                if (intval($v1["quantity"]) > intval($product->quantite) - 1) {
                    return response()->json([
                        'error'   => true,
                        'message' => 'Stock dépassé',
                    ]);
                }
            }
        }

        Cart::session($userId)->add([
            'id'       => $product->id,
            'name'     => $product->libelle,
            'quantity' => 1,
            'price'    => intval($product->selling_price),
        ]);

        return response()->json(['message' => 'Produit incrementé']);
    }

    public function decreaseQuantity($productId)
    {
        $userId    = auth()->user()->id;
        $cartItems = Cart::session($userId)->getContent();

        foreach ($cartItems as $cartItem) {
            if ($cartItem->id == $productId) {

                Cart::session($userId)->remove($cartItem->id);

                if (($cartItem->quantity - 1) == 0) {
                    return response()->json(['message' => 'Produit supprimé']);
                }

                Cart::session($userId)->add([
                    'id'       => $cartItem->id,
                    'name'     => $cartItem->name,
                    'quantity' => $cartItem->quantity - 1,
                    'price'    => $cartItem->price,
                ]);

                return response()->json(['message' => 'Produit decrementé']);
            }
        }

        return response()->json(['message' => 'Produit introuvable'], 404);
    }

    public function removeItem($productId)
    {
        $userId    = auth()->user()->id;
        $cartItems = Cart::session($userId)->getContent();

        foreach ($cartItems as $cartItem) {
            if ($cartItem->id == $productId) {
                Cart::session($userId)->remove($cartItem->id);
                return response()->json(['message' => 'Produit supprimé']);
            }
        }

        return response()->json(['message' => 'Produit introuvable'], 404);
    }

    public function addOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id'     => 'required|integer',
            'tva'           => 'required|integer',
            'remise'        => 'required|integer',
            'espece'        => 'required|integer',
            'qte_total'     => 'required|integer',
            'paid_method_id'=> 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "error" => true,
                "info"  => "Champ requis manquant",
                "data"  => $validator->errors()
            ]);
        }

        $userId            = auth()->user()->id;
        $data_pos          = [];
        $data_pos_cart_items = [];

        $data_pos["created_user"]   = $userId;
        $data_pos["client_id"]      = $request->client_id;
        $data_pos["tva"]            = $request->tva;
        $data_pos["remise"]         = $request->remise;
        $data_pos["espece"]         = $request->espece;
        $data_pos["monnaie"]        = $request->monnaie;
        $data_pos["qte_total"]      = $request->qte_total;
        $data_pos["paid_method_id"] = $request->paid_method_id;
        $data_pos["print_status"]   = 0;
        $data_pos["status"]         = 1;
        $data_pos["transaction_id"] = strtoupper(uniqid());

        $pos       = Pos::create($data_pos);
        $cartItems = Cart::session($userId)->getContent();

        foreach ($cartItems as $cartItem) {
            $produit = Produit::find($cartItem->id);

            $data_pos_cart_items[] = [
                'pos_id'       => $pos->id,
                'item_id'      => $cartItem->id,
                // Instantané : la ligne reste lisible même si le produit est supprimé
                'libelle'      => $produit->libelle ?? $cartItem->name,
                'image'        => $produit->image ?? null,
                'qte'          => $cartItem->quantity,
                'price'        => $cartItem->price,
                'price_by_qte' => $cartItem->price * $cartItem->quantity,
                'status'       => 1,
                'created_user' => $userId,
            ];

            $this->checkout($cartItem->id, $cartItem->quantity);
        }

        if (!empty($data_pos_cart_items)) {
            PosCartItem::insert($data_pos_cart_items);
        }

        $this->clearCart();

        return response()->json([
            'status'  => 'success',
            'pos_id'  => $pos->id,
            'message' => 'Commande effectuée',
        ], 201);
    }

    public function checkout($id, $qte)
    {
        DB::beginTransaction();

        try {
            $product = Produit::findOrFail($id);

            if ($product->quantite < $qte) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Stock insuffisant',
                ]);
            }

            $product->quantite -= $qte;
            $product->save();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
}