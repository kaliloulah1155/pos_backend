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
    public function index()
    {
        try {
            $produits = DB::table('produits')
                ->select(
                    'produits.id',
                    'produits.libelle',
                    'produits.code',
                    'produits.barcode',        // ← ajouté
                    'produits.image',
                    'produits.buying_price',
                    'produits.selling_price',
                    'produits.fournisseur_id',
                    'produits.quantite',
                    'produits.online',
                    'categories.libelle as category_name'
                )
                ->leftJoin('categorie_produit', 'produits.id', '=', 'categorie_produit.produit_id')
                ->leftJoin('categories', 'categorie_produit.categorie_id', '=', 'categories.id')
                ->whereNull('produits.deleted_at')
                ->get();

            $mappedProduits = $produits->groupBy('id')->map(function ($produitGroup) {
                $firstProduit = $produitGroup->first();
                $user         = User::find($firstProduit->fournisseur_id);

                return [
                    'id'             => $firstProduit->id,
                    'libelle'        => $firstProduit->libelle,
                    'code'           => $firstProduit->code,
                    'barcode'        => $firstProduit->barcode,   // ← ajouté
                    'online'         => (int) $firstProduit->online,
                    'fournisseur_id' => $user ? $user->id : null,
                    'fournisseur'    => $user ? $user->nom . ' ' . $user->prenoms : "NEANT",
                    'categories'     => $produitGroup->pluck('category_name')->all(),
                    'image'          => $firstProduit->image
                                        ? env('IMAGE_PATH_PRODUITS') . $firstProduit->image
                                        : null,
                    'buying_price'   => (new AmountFormatService)->formatAmount($firstProduit->buying_price) . ' F CFA',
                    'selling_price'  => (new AmountFormatService)->formatAmount($firstProduit->selling_price) . ' F CFA',
                    'quantite'       => $firstProduit->quantite,
                ];
            });

            return response()->json([
                'data' => $mappedProduits->values()->all(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.index',
            ]);
        }
    }

    public function stocks()
    {
        try {
            $produits = DB::table('produits')
                ->select(
                    'produits.id',
                    'produits.libelle',
                    'produits.code',
                    'produits.barcode',        // ← ajouté
                    'produits.image',
                    'produits.buying_price',
                    'produits.selling_price',
                    'produits.fournisseur_id',
                    'produits.quantite',
                    'produits.online',
                    'categories.libelle as category_name'
                )
                ->leftJoin('categorie_produit', 'produits.id', '=', 'categorie_produit.produit_id')
                ->leftJoin('categories', 'categorie_produit.categorie_id', '=', 'categories.id')
                ->whereNull('produits.deleted_at')
                ->get();

            $mappedProduits = $produits->groupBy('id')->map(function ($produitGroup) {
                $firstProduit = $produitGroup->first();
                $stockStatus  = intval($firstProduit->quantite) <= 0 ? "Rupture" : "Stock";
                $user         = User::find($firstProduit->fournisseur_id);

                return [
                    'id'               => $firstProduit->id,
                    'libelle'          => $firstProduit->libelle,
                    'code'             => $firstProduit->code,
                    'barcode'          => $firstProduit->barcode,  // ← ajouté
                    'online'           => (int) $firstProduit->online,
                    'fournisseur'      => $user ? $user->nom . ' ' . $user->prenoms : "NEANT",
                    'categories'       => $produitGroup->pluck('category_name')->all(),
                    'image'            => $firstProduit->image
                                          ? env('IMAGE_PATH_PRODUITS') . $firstProduit->image
                                          : null,
                    'val_buying_price' => intval($firstProduit->buying_price),
                    'buying_price'     => (new AmountFormatService)->formatAmount($firstProduit->buying_price) . ' F CFA',
                    'selling_price'    => (new AmountFormatService)->formatAmount($firstProduit->selling_price) . ' F CFA',
                    'val_selling_price'=> intval($firstProduit->selling_price),
                    'stock'            => $stockStatus,
                    'quantite'         => $firstProduit->quantite,
                ];
            });

            return response()->json([
                'data' => $mappedProduits->values()->all(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.stocks',
            ]);
        }
    }

    public function create() {}

    public function store(StoreRequest $request)
    {
        try {
            $code = Produit::formatChaine($request->libelle);

            $existingProduit = Produit::where('code', $code)->first();
            while ($existingProduit) {
                $code            = Produit::formatChaine(Produit::generateRandomAlphaCode());
                $existingProduit = Produit::where('code', $code)->first();
            }

            $barcode = $request->input('barcode');
            $barcode = !is_null($barcode) ? trim($barcode) : null;
            if ($barcode === '') {
                $barcode = null;
            }

            // ── Vérifier unicité du barcode ──────────────────────
            if ($barcode) {
                $existingBarcode = Produit::withTrashed()->where('barcode', $barcode)->first();
                if ($existingBarcode) {
                    return response()->json([
                        'error'   => true,
                        'message' => 'Ce barcode est déjà utilisé par un autre produit.',
                    ], 422);
                }
            }

            $produit = Produit::create([
                'libelle'        => $request->libelle,
                'code'           => $code,
                'barcode'        => $barcode,
                'buying_price'   => $request->buying_price  ?? 0,
                'selling_price'  => $request->selling_price ?? 0,
                'quantite'       => $request->quantite       ?? 0,
                'online'         => (int) $request->input('online', 1),
                'fournisseur_id' => $request->fournisseur_id ?? null,
                'created_user'   => Auth::id(),
            ]);

            $produit->categories()->sync(json_decode($request->input('categories')));

            if ($request->hasFile('image')) {
                (new ImageService)->updateImage($produit, $request, '/images/produits/', 'update');
                $produit->save();
            }

            return response()->json($produit, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.store',
            ]);
        }
    }

    public function show($id) {}
    public function edit($id) {}

    public function update(Request $request, int $id)
    {
        try {
            $produit = Produit::find($id);

            if (!$produit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produit avec l\'id ' . $id . ' n\'existe pas!',
                ], 200);
            }

            $barcode = $request->input('barcode');
            $barcode = !is_null($barcode) ? trim($barcode) : null;
            if ($barcode === '') {
                $barcode = null;
            }

            // ── Vérifier unicité du barcode (ignorer le produit en cours) ──
            if ($barcode && $barcode !== $produit->barcode) {
                $existingBarcode = Produit::withTrashed()
                                          ->where('barcode', $barcode)
                                          ->where('id', '!=', $id)
                                          ->first();
                if ($existingBarcode) {
                    return response()->json([
                        'error'   => true,
                        'message' => 'Ce barcode est déjà utilisé par un autre produit.',
                    ], 422);
                }
            }

            if ($request->hasFile('image')) {
                (new ImageService)->updateImage($produit, $request, '/images/produits/', 'update');
            }

            $produit->libelle        = $request->libelle        ?? $produit->libelle;
            $produit->barcode        = !is_null($barcode) ? $barcode : $produit->barcode;
            $produit->buying_price   = intval($request->buying_price)   ?? $produit->buying_price;
            $produit->selling_price  = intval($request->selling_price)  ?? $produit->selling_price;
            $produit->quantite       = intval($request->quantite)       ?? $produit->quantite;
            $produit->online         = $request->has('online') ? (int) $request->online : $produit->online;
            $produit->fournisseur_id = intval($request->fournisseur_id) ?? $produit->fournisseur_id;
            $produit->updated_user   = Auth::id();

            $produit->categories()->sync(json_decode($request->input('categories')));
            $produit->save();

            return response()->json([
                'success' => true,
                'message' => 'Produit avec l\'id ' . $id . ' a été mis à jour!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.update',
            ]);
        }
    }

    /**
     * Bascule rapide de la disponibilité d'un produit sur la boutique en ligne.
     * Endpoint léger : ne touche ni aux catégories ni à l'image.
     */
    public function setOnline(Request $request, int $id)
    {
        try {
            $produit = Produit::find($id);
            if (!$produit) {
                return response()->json([
                    'success' => false,
                    'message' => "Produit introuvable",
                ], 404);
            }

            $produit->online       = (int) $request->input('online', 1) ? 1 : 0;
            $produit->updated_user = Auth::id();
            $produit->save();

            return response()->json([
                'success' => true,
                'online'  => $produit->online,
                'message' => $produit->online ? 'Produit visible en ligne' : 'Produit masqué en ligne',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.setOnline',
            ], 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $record  = Produit::where('id', $id)->get();
            $current = Carbon::now();

            if (count($record) > 0) {
                Produit::where('id', $id)->update([
                    'deleted_at'   => $current,
                    'deleted_user' => Auth::id(),
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Produit avec l\'id ' . $id . ' a été supprimé!',
                ], 201);
            } else {
                return response()->json([
                    'result'  => false,
                    'message' => "Ce Produit n'existe pas",
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error'   => $e->getMessage(),
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
                return response()->json([
                    'success' => true,
                    'message' => 'Produit avec l\'id ' . $id . ' a été supprimé!',
                ], 201);
            } else {
                return response()->json([
                    'result'  => false,
                    'message' => "Ce Produit n'existe pas",
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.delete',
            ]);
        }
    }

    public function pos_categorie()
    {
        try {
            // Uniquement les catégories ayant au moins un produit
            $categories = Categorie::where("statut", 1)
                ->whereHas('produits')
                ->get();

            $mappedCategories = $categories->map(function ($categorie) {
                return [
                    'id'      => $categorie->id,
                    'libelle' => $categorie->libelle,
                ];
            });

            return response()->json(['data' => $mappedCategories]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.pos_categorie',
            ]);
        }
    }

    public function pos_produit_by_categorie($categoryId)
    {
        try {
            $products = Produit::with('categories')
                ->whereHas('categories', function ($query) use ($categoryId) {
                    $query->where('categories.id', intval($categoryId));
                })
                ->get()
                ->map(function ($product) {
                    $product->stock         = $product->quantite <= 0 ? "Rupture" : "Stock";
                    $product->image         = $product->image
                                              ? env('IMAGE_PATH_PRODUITS') . $product->image
                                              : null;
                    $product->buying_price  = (new AmountFormatService)->formatAmount($product->buying_price) . ' F CFA';
                    $product->selling_price = (new AmountFormatService)->formatAmount($product->selling_price) . ' F CFA';
                    unset($product->categories);
                    return $product;
                });

            return response()->json(['data' => $products]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Something went wrong in ProduitController.pos_produit_by_categorie',
            ]);
        }
    }
}
