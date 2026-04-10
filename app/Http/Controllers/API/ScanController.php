<?php

namespace App\Http\Controllers\API;

use App\Events\ProductScanned;
use App\Http\Controllers\Controller;
use App\Models\Pos;
use App\Models\PosCartItem;
use App\Models\Produit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ScanController extends Controller
{
    /**
     * 🚀 SCAN ULTRA RAPIDE
     * POST /api/scan
     * (Scan + ajout panier en une seule requête)
     */

    public function showQr($id)
    {
        $produit = Produit::findOrFail($id);

        return view('qr', compact('produit'));
    }
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'code'   => 'required|string',
            'pos_id' => 'nullable|integer|exists:pos,id',
        ]);

        $user = Auth::user();
        $code = trim($request->code);

        // ⚡ Récupération produit optimisée
        $produit = Produit::select('id', 'libelle', 'selling_price', 'quantite', 'code')
            ->where('code', $code)
            ->first();

        if (!$produit) {
            return response()->json(['message' => 'Produit introuvable'], 404);
        }

        if ($produit->quantite <= 0) {
            return response()->json(['message' => 'Stock insuffisant'], 422);
        }

        // ⚡ POS rapide
        $pos = $request->pos_id
            ? Pos::find($request->pos_id)
            : Pos::firstOrCreate(
                ['client_id' => $user->id, 'status' => 0],
                ['created_user' => $user->id, 'qte_total' => 0]
            );

        // ⚡ Ajout / incrément panier
        $item = PosCartItem::firstOrNew([
            'pos_id'  => $pos->id,
            'item_id' => $produit->id,
        ]);

        if ($item->exists) {
            $item->qte += 1;
        } else {
            $item->qte = 1;
            $item->price = $produit->selling_price;
            $item->status = 1;
            $item->created_user = $user->id;
        }

        $item->price_by_qte = $item->price * $item->qte;
        $item->save();

        // ⚡ Réponse optimisée (ultra légère)
        $cartItem = [
            'id'           => $item->id,
            'pos_id'       => $pos->id,
            'product_id'   => $produit->id,
            'name'         => $produit->libelle,
            'code'         => $produit->code,
            'price'        => (float) $item->price,
            'quantity'     => $item->qte,
            'total_price'  => (float) $item->price_by_qte,
        ];

        // ⚡ Broadcast temps réel (POS multi caisses)
        broadcast(new ProductScanned($cartItem, $user->id))->toOthers();

        return response()->json([
            'message'   => 'OK',
            'cart_item' => $cartItem,
            'pos_id'    => $pos->id,
        ]);
    }
}
