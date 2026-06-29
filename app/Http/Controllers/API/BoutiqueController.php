<?php

namespace App\Http\Controllers\API;

use App\Events\OnlineOrderPlaced;
use App\Events\OnlineOrderUpdated;
use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Entreprise;
use App\Models\Pos;
use App\Models\PosCartItem;
use App\Models\Produit;
use App\Services\AmountFormatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Validator;

/**
 * Boutique en ligne (landing page + QR code).
 *
 * Méthodes PUBLIQUES (aucune authentification) : products, categories, store, qrcode.
 * Méthodes ADMIN (auth:sanctum)                : index, show, updateStatus.
 */
class BoutiqueController extends Controller
{
    /* =========================================================
     |  PARTIE PUBLIQUE (client)
     |========================================================= */

    /**
     * Liste publique des produits disponibles à la commande.
     * Renvoie le prix numérique (pour le calcul du panier) + le prix formaté.
     */
    public function products()
    {
        try {
            $produits = DB::table('produits')
                ->select(
                    'produits.id',
                    'produits.libelle',
                    'produits.image',
                    'produits.selling_price',
                    'produits.quantite',
                    'categories.id as categorie_id',
                    'categories.libelle as category_name'
                )
                ->leftJoin('categorie_produit', 'produits.id', '=', 'categorie_produit.produit_id')
                ->leftJoin('categories', 'categorie_produit.categorie_id', '=', 'categories.id')
                ->whereNull('produits.deleted_at')
                ->where('produits.online', 1)
                ->get();

            $mapped = $produits->groupBy('id')->map(function ($group) {
                $p = $group->first();
                return [
                    'id'              => $p->id,
                    'libelle'         => $p->libelle,
                    'image'           => $p->image ? env('IMAGE_PATH_PRODUITS') . $p->image : null,
                    'prix'            => intval($p->selling_price),
                    'prix_format'     => (new AmountFormatService)->formatAmount($p->selling_price) . ' F CFA',
                    'quantite'        => intval($p->quantite),
                    'en_stock'        => intval($p->quantite) > 0,
                    'categorie_ids'   => $group->pluck('categorie_id')->filter()->unique()->values()->all(),
                    'categorie_noms'  => $group->pluck('category_name')->filter()->unique()->values()->all(),
                ];
            });

            return response()->json(['data' => $mapped->values()->all()]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Erreur dans BoutiqueController.products',
            ], 500);
        }
    }

    /**
     * Liste publique des catégories possédant au moins un produit.
     */
    public function categories()
    {
        try {
            $categories = Categorie::where('statut', 1)
                ->whereHas('produits', fn ($q) => $q->where('online', 1))
                ->orderBy('position')
                ->get()
                ->map(fn ($c) => [
                    'id'      => $c->id,
                    'libelle' => $c->libelle,
                    'icone'   => $c->icone,
                ]);

            return response()->json(['data' => $categories->values()->all()]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Erreur dans BoutiqueController.categories',
            ], 500);
        }
    }

    /**
     * Enregistre une commande passée en ligne par un client.
     * Le total est recalculé côté serveur (on ne fait pas confiance au client).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name'    => 'required|string|max:255',
            'customer_phone'   => 'required|string|max:50',
            'customer_address' => 'nullable|string|max:255',
            'note'             => 'nullable|string',
            'items'            => 'required|array|min:1',
            'items.*.id'       => 'required|integer',
            'items.*.qte'      => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => true,
                'message' => 'Informations de commande invalides',
                'data'    => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $items     = collect($request->items);
            $total     = 0;
            $cartLines = [];

            foreach ($items as $line) {
                $produit = Produit::find($line['id']);
                if (!$produit) {
                    continue;
                }

                $qte   = max(1, intval($line['qte']));
                $price = intval($produit->selling_price);
                $total += $price * $qte;

                $cartLines[] = [
                    'item_id'      => $produit->id,
                    'qte'          => $qte,
                    'price'        => $price,
                    'price_by_qte' => $price * $qte,
                    'status'       => 1,
                ];
            }

            if (empty($cartLines)) {
                DB::rollBack();
                return response()->json([
                    'error'   => true,
                    'message' => 'Aucun produit valide dans la commande',
                ], 422);
            }

            $pos = Pos::create([
                'client_id'        => null,
                'tva'              => 0,
                'remise'           => 0,
                'espece'           => 0,
                'monnaie'          => 0,
                'qte_total'        => $total,
                'paid_method_id'   => null,
                'print_status'     => 0,
                'status'           => 1,
                'order_type'       => 'online',
                'order_status'     => 'pending',
                'customer_name'    => $request->customer_name,
                'customer_phone'   => $request->customer_phone,
                'customer_address' => $request->customer_address,
                'note'             => $request->note,
                'transaction_id'   => strtoupper(uniqid('ON-')),
            ]);

            foreach ($cartLines as &$line) {
                $line['pos_id']     = $pos->id;
                $line['created_at'] = now();
                $line['updated_at'] = now();
            }
            PosCartItem::insert($cartLines);

            DB::commit();

            $payload = [
                'order_id'       => $pos->id,
                'transaction_id' => $pos->transaction_id,
                'customer_name'  => $pos->customer_name,
                'customer_phone' => $pos->customer_phone,
                'total'          => $total,
                'nb_items'       => count($cartLines),
                'created_at'     => $pos->created_at,
            ];

            // Notification temps réel du point de vente
            try {
                broadcast(new OnlineOrderPlaced($payload));
            } catch (\Throwable $e) {
                // Le broadcast ne doit jamais bloquer la commande
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Votre commande a bien été reçue. La boutique va vous contacter.',
                'order'   => $payload,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Erreur dans BoutiqueController.store',
            ], 500);
        }
    }

    /**
     * Informations publiques de la boutique (nom + logo de l'entreprise).
     * Le logo est renvoyé en base64 pour un usage direct (avatar, canvas QR
     * sans problème de CORS / canvas "tainted").
     */
    public function info()
    {
        try {
            $e    = Entreprise::first();
            $logo = null;

            if ($e && $e->image) {
                $path = public_path('images/entreprise/' . $e->image);
                if (is_file($path)) {
                    $mime = @mime_content_type($path) ?: 'image/png';
                    $logo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
                }
            }

            return response()->json([
                'name'    => $e ? $e->libelle      : null,
                'logo'    => $logo,
                'phone'   => $e ? $e->phone_1      : null,
                'address' => $e ? $e->localisation : null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Erreur dans BoutiqueController.info',
            ], 500);
        }
    }

    /**
     * Génère le QR code (SVG) pointant vers la boutique en ligne.
     * Le front passe sa propre URL : /boutique-qr?url=https://...
     */
    public function qrcode(Request $request)
    {
        $url = $request->query('url', env('BOUTIQUE_URL', env('APP_URL')));

        $svg = QrCode::format('svg')
            ->size(320)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($url);

        return response($svg, 200)->header('Content-Type', 'image/svg+xml');
    }

    /**
     * Affiche complète (SVG vectoriel) prête à imprimer :
     * dégradé, logo, nom + téléphone + adresse de l'entreprise, accroche et QR.
     * Paramètres optionnels : url, name, whatsapp, address.
     */
    public function poster(Request $request)
    {
        $e = Entreprise::first();

        $url      = $request->query('url', env('BOUTIQUE_URL', env('APP_URL')));
        // Nom = raison sociale de l'entreprise en priorité (modifiable dans Entreprise)
        $name     = $request->query('name', $e ? $e->libelle : env('BOUTIQUE_NAME', 'Ma Boutique'));
        // Téléphone et adresse masqués par défaut (affichés seulement si fournis explicitement)
        $whatsapp = $request->query('whatsapp', '');
        $address  = $request->query('address', '');

        // QR interne (on retire la déclaration XML pour l'imbriquer)
        $qr = QrCode::format('svg')->size(360)->margin(0)->errorCorrection('H')->generate($url);
        $qr = preg_replace('/<\?xml.*?\?>/', '', $qr);

        // Logo entreprise (base64) à incruster, si disponible
        $logoTag = '';
        if ($e && $e->image) {
            $path = public_path('images/entreprise/' . $e->image);
            if (is_file($path)) {
                $mime = @mime_content_type($path) ?: 'image/png';
                $data = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
                $logoTag = '<clipPath id="logoClip"><circle cx="410" cy="150" r="56"/></clipPath>'
                    . '<circle cx="410" cy="150" r="60" fill="#ffffff"/>'
                    . '<image x="350" y="90" width="120" height="120" preserveAspectRatio="xMidYMid slice" clip-path="url(#logoClip)" href="' . $data . '"/>';
            }
        }

        $name     = htmlspecialchars($name, ENT_QUOTES);
        $whatsapp = htmlspecialchars($whatsapp, ENT_QUOTES);
        $address  = htmlspecialchars($address, ENT_QUOTES);
        $hasLogo  = $logoTag !== '';
        $nameY    = $hasLogo ? 268 : 170;
        $subY     = $hasLogo ? 308 : 210;

        // Bandeau d'appel à l'action : téléphone si fourni, sinon message neutre
        if ($whatsapp !== '') {
            $ctaBand = '<rect x="210" y="960" width="400" height="64" rx="32" fill="#25D366"/>'
                . '<text x="410" y="1001" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" font-weight="bold" fill="#ffffff">WhatsApp : ' . $whatsapp . '</text>';
        } else {
            $ctaBand = '<rect x="210" y="960" width="400" height="64" rx="32" fill="#ffffff" opacity="0.15"/>'
                . '<text x="410" y="1001" text-anchor="middle" font-family="Arial, sans-serif" font-size="26" font-weight="bold" fill="#ffffff">Commandez en ligne 24h/24</text>';
        }

        // Adresse optionnelle, suivie de la mention de paiement
        if ($address !== '') {
            $footerTag = '<text x="410" y="1070" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" fill="#ffffff" opacity="0.92">' . $address . '</text>'
                . '<text x="410" y="1110" text-anchor="middle" font-family="Arial, sans-serif" font-size="18" fill="#ffffff" opacity="0.7">Paiement à la livraison ou au retrait en boutique</text>';
        } else {
            $footerTag = '<text x="410" y="1085" text-anchor="middle" font-family="Arial, sans-serif" font-size="20" fill="#ffffff" opacity="0.8">Paiement à la livraison ou au retrait en boutique</text>';
        }

        $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="820" height="1180" viewBox="0 0 820 1180">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#0d47a1"/>
      <stop offset="0.55" stop-color="#1565c0"/>
      <stop offset="1" stop-color="#4527a0"/>
    </linearGradient>
    <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="10" stdDeviation="18" flood-color="#000000" flood-opacity="0.28"/>
    </filter>
  </defs>

  <rect width="820" height="1180" fill="url(#bg)"/>

  <!-- Décor -->
  <circle cx="60" cy="80" r="220" fill="#ffffff" opacity="0.06"/>
  <circle cx="800" cy="1040" r="260" fill="#ffffff" opacity="0.06"/>
  <circle cx="740" cy="120" r="90" fill="#ffffff" opacity="0.05"/>

  $logoTag

  <!-- Nom de la boutique -->
  <text x="410" y="$nameY" text-anchor="middle" font-family="Arial, sans-serif" font-size="56" font-weight="bold" fill="#ffffff">$name</text>
  <text x="410" y="$subY" text-anchor="middle" font-family="Arial, sans-serif" font-size="26" letter-spacing="4" fill="#ffffff" opacity="0.85">COMMANDE EN LIGNE</text>

  <!-- Carte blanche avec QR -->
  <rect x="160" y="345" width="500" height="560" rx="34" fill="#ffffff" filter="url(#shadow)"/>
  <text x="410" y="410" text-anchor="middle" font-family="Arial, sans-serif" font-size="30" font-weight="bold" fill="#0d47a1">Scannez &amp; commandez</text>
  <rect x="225" y="440" width="370" height="370" rx="18" fill="none" stroke="#0d47a1" stroke-width="4"/>
  <g transform="translate(230,445)">$qr</g>
  <text x="410" y="858" text-anchor="middle" font-family="Arial, sans-serif" font-size="20" fill="#5f6368">Pointez l'appareil photo de votre téléphone</text>

  <!-- Pied de page : appel à l'action + (adresse optionnelle) + paiement -->
  $ctaBand
  $footerTag
</svg>
SVG;

        return response($svg, 200)->header('Content-Type', 'image/svg+xml');
    }

    /* =========================================================
     |  PARTIE ADMIN (point de vente)
     |========================================================= */

    /**
     * Liste des commandes en ligne pour le point de vente.
     * Filtre optionnel : ?statut=pending|accepted|rejected|completed
     */
    public function index(Request $request)
    {
        try {
            $startDate = $request->filled('start_date') ? $request->start_date . ' 00:00:00' : null;
            $endDate   = $request->filled('end_date')   ? $request->end_date   . ' 23:59:59' : null;

            $query = DB::table('pos as ps')
                ->select(
                    'ps.id as order_id',
                    'ps.transaction_id',
                    'ps.customer_name',
                    'ps.customer_phone',
                    'ps.customer_address',
                    'ps.note',
                    'ps.qte_total as total',
                    'ps.order_status',
                    'ps.created_at',
                    DB::raw('(SELECT COUNT(*) FROM pos_cart_items WHERE pos_cart_items.pos_id = ps.id) as nb_items')
                )
                ->where('ps.order_type', 'online')
                ->orderBy('ps.created_at', 'desc');

            if ($request->filled('statut')) {
                $query->where('ps.order_status', $request->statut);
            }
            if ($startDate) {
                $query->where('ps.created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('ps.created_at', '<=', $endDate);
            }

            $orders = $query->get();

            $summaryQuery = DB::table('pos')
                ->where('order_type', 'online');
            if ($startDate) {
                $summaryQuery->where('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $summaryQuery->where('created_at', '<=', $endDate);
            }

            $summary = $summaryQuery
                ->selectRaw("
                    COUNT(*) as total_orders,
                    COALESCE(SUM(CASE WHEN order_status = 'pending'   THEN 1 ELSE 0 END), 0) as pending,
                    COALESCE(SUM(CASE WHEN order_status = 'processed' THEN 1 ELSE 0 END), 0) as processed,
                    COALESCE(SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled
                ")
                ->first();

            return response()->json([
                'success' => true,
                'data'    => $orders,
                'summary' => $summary,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Erreur dans BoutiqueController.index',
            ], 500);
        }
    }

    /**
     * Détail d'une commande en ligne (avec son panier).
     */
    public function show($id)
    {
        try {
            $order = DB::table('pos')
                ->where('id', $id)
                ->where('order_type', 'online')
                ->first();

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Commande introuvable'], 404);
            }

            $items = DB::table('pos_cart_items as item')
                ->select('pod.libelle as produit', 'item.qte', 'item.price', 'item.price_by_qte')
                ->leftJoin('produits as pod', 'item.item_id', '=', 'pod.id')
                ->where('item.pos_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'order'   => $order,
                'items'   => $items,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Erreur dans BoutiqueController.show',
            ], 500);
        }
    }

    /**
     * Met à jour le statut d'une commande en ligne (back-office).
     * pending   => en cours (commande reçue, non traitée)
     * processed => traité (décrémente le stock une seule fois)
     * cancelled => annulé
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'order_status' => 'required|in:pending,processed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => 'Statut invalide'], 422);
        }

        DB::beginTransaction();
        try {
            $pos = Pos::where('id', $id)->where('order_type', 'online')->first();

            if (!$pos) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Commande introuvable'], 404);
            }

            $newStatus = $request->order_status;

            // On ne décrémente le stock qu'une seule fois, au passage à "traité".
            if ($newStatus === 'processed' && $pos->order_status !== 'processed') {
                $items = PosCartItem::where('pos_id', $pos->id)->get();
                foreach ($items as $item) {
                    $produit = Produit::find($item->item_id);
                    if ($produit) {
                        $produit->quantite = max(0, intval($produit->quantite) - intval($item->qte));
                        $produit->save();
                    }
                }
            }

            $pos->order_status = $newStatus;
            $pos->save();

            DB::commit();

            // Notifie en temps réel (la cloche recompte les commandes en cours)
            try {
                broadcast(new OnlineOrderUpdated($pos->id, $newStatus));
            } catch (\Throwable $e) {
                // Le broadcast ne doit jamais bloquer la mise à jour
            }

            return response()->json([
                'success' => true,
                'message' => 'Statut de la commande mis à jour',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Erreur dans BoutiqueController.updateStatus',
            ], 500);
        }
    }

    /**
     * Supprime définitivement une commande en ligne et son panier.
     * Si la commande avait été "traitée", le stock est restitué.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $pos = Pos::where('id', $id)->where('order_type', 'online')->first();

            if (!$pos) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Commande introuvable'], 404);
            }

            $items = PosCartItem::where('pos_id', $pos->id)->get();

            // Restituer le stock si la commande avait été traitée (stock décrémenté)
            if ($pos->order_status === 'processed') {
                foreach ($items as $item) {
                    $produit = Produit::find($item->item_id);
                    if ($produit) {
                        $produit->quantite = intval($produit->quantite) + intval($item->qte);
                        $produit->save();
                    }
                }
            }

            PosCartItem::where('pos_id', $pos->id)->delete();
            $pos->delete();

            DB::commit();

            try {
                broadcast(new OnlineOrderUpdated((int) $id, 'deleted'));
            } catch (\Throwable $e) {
                // ne bloque pas la suppression
            }

            return response()->json([
                'success' => true,
                'message' => 'Commande supprimée',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error'   => $e->getMessage(),
                'message' => 'Erreur dans BoutiqueController.destroy',
            ], 500);
        }
    }
}
