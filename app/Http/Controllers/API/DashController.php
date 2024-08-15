<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashController extends Controller
{
    
      /**
 *
 * @OA\Get (
 *     path="/stats",
 *     tags={"Dashboard"},
 *     summary="Liste des stats | start_date et end_date ne sont pas obligatoires",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="start_date",
 *         in="query",
 *         required=false,
 *         @OA\Schema(
 *             type="string",
 *             format="date-time"
 *         ),
 *         description="Date et heure de début 2024-05-28 00:00:00"
 *     ),
 *     @OA\Parameter(
 *         name="end_date",
 *         in="query",
 *         required=false,
 *         @OA\Schema(
 *             type="string",
 *             format="date-time"
 *         ),
 *         description="Date et heure de fin 2024-05-28 00:00:00"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success"
 *     )
 * )
 */

    public function stats(Request $request)
    {
        // Initialisation des dates de début et de fin
        $start_date = $request->query('start_date', Carbon::now()->startOfDay()->format('Y-m-d H:i:s'));
        $end_date = $request->query('end_date', Carbon::now()->endOfDay()->format('Y-m-d H:i:s'));

        if (is_null($start_date) || is_null($end_date)) {
            $start_date = Carbon::today()->startOfDay();
            $end_date = Carbon::today()->endOfDay();
        }

        $start_year = Carbon::parse($start_date)->year;
        $end_year = Carbon::parse($end_date)->year;

        // Initialisation des tableaux pour les données
        $produits = [];
        $ventes_filter = [];

        // Nombre total de produits
        $produits["nombre"] = Produit::count();

         // Produits en rupture de stock
         $produits["stock"] = Produit::where('quantite', '>', 0)->count();
        // Produits en rupture de stock
        $produits["rupture"] = Produit::where('quantite', '=', 0)->count();



        // Produits en dessous du seuil
        $produits["seuil"] = Produit::where('quantite', '=', env('SEUIL_PRODUIT'))->count();

        // Récupération des ventes filtrées par date
        $ordersQuery = DB::table('pos_cart_items as item')
            ->select(
                'ps.created_at',
                'ps.id as order_id',
                'pod.libelle as produit',
                'item.qte',
                'item.price',
                'item.price_by_qte'
            )
            ->leftJoin('pos as ps', 'item.pos_id', '=', 'ps.id')
            ->leftJoin('produits as pod', 'item.item_id', '=', 'pod.id')
            ->whereBetween('ps.created_at', [$start_date, $end_date])
            ->whereNotNull('ps.id')
            ->where('ps.created_user', Auth::id())
            ->get();

        // Produits critiques (en rupture ou au seuil)
        $produits_seuil_critique = DB::table('produits')
            ->select('libelle', 'quantite')
            ->where('quantite', '=', 0)
            ->orWhere('quantite', '=', env('SEUIL_PRODUIT'))
            ->get();

        //BEGIN:: Top 10 des produits les plus vendus

        $query1 = DB::table('pos_cart_items as item')
            ->select(
                'pod.libelle as produit_top',
                DB::raw('SUM(item.qte) as total_vendu')
            )
            ->leftJoin('pos as ps', 'item.pos_id', '=', 'ps.id')
            ->leftJoin('produits as pod', 'item.item_id', '=', 'pod.id')
            ->whereNotNull('ps.id')
            ->whereNull('pod.deleted_at');
        
             // Si les deux dates sont dans la même année
            if ($start_year == $end_year) {
                $query1->whereYear('ps.created_at', '=', $start_year);
            } else {
                // Si les dates couvrent une période plus longue
                $query1->whereBetween('ps.created_at', [$start_date, $end_date]);
            }

            $topProducts = $query1->groupBy('pod.libelle')
            ->orderByDesc('total_vendu')
            ->limit(env('TOP_PRODUIT', 10)) // Limite de top produits à afficher
            ->get();

        // Extraction des libellés de produits et des quantités vendues
        $produit_top = [];
        $totalVendu = [];

        foreach ($topProducts as $product) {
            $produit_top[] = $product->produit_top;
            $totalVendu[] = (int) $product->total_vendu;
        }

        //END:: Top 10 des produits les plus vendus

        //BEGIN::Top 10 des produits les plus vendus en fonction de la valeur totale des ventes
        $query2 = DB::table('pos_cart_items as item')
            ->select(
                'pod.libelle as produit_top',
                DB::raw('SUM(item.price_by_qte) as total_valeur')
            )
            ->leftJoin('pos as ps', 'item.pos_id', '=', 'ps.id')
            ->leftJoin('produits as pod', 'item.item_id', '=', 'pod.id')
            ->whereNotNull('ps.id')
            ->whereNull('pod.deleted_at');

             // Si les deux dates sont dans la même année
             if ($start_year == $end_year) {
                $query2->whereYear('ps.created_at', '=', $start_year);
            } else {
                // Si les dates couvrent une période plus longue
                $query2->whereBetween('ps.created_at', [$start_date, $end_date]);
            }
            $topProductsByValue =$query2->groupBy('pod.libelle')
            ->orderByDesc('total_valeur')
            ->limit(env('TOP_PRODUIT', 10)) // Limite à 10 produits les plus vendus par valeur
            ->get();

// Initialiser les tableaux pour les produits et la valeur totale des ventes
        $produit_top_value = [];
        $totalValeur = [];

// Remplir les tableaux avec les résultats de la requête
        foreach ($topProductsByValue as $product) {
            $produit_top_value[] = $product->produit_top;
            $totalValeur[] = (int) $product->total_valeur;
        }

         //END::Top 10 des produits les plus vendus en fonction de la valeur totale des ventes

         //BEGIN::VENTES
         $summaryOrders= DB::table('pos as ps')
            ->select(
                DB::raw('COALESCE(CAST(SUM(ps.qte_total) AS INTEGER), 0) as total_sum'),
                DB::raw('COUNT(ps.id) as total_transactions')
            )
            ->whereBetween('ps.created_at', [$start_date, $end_date])
            ->whereNotNull('ps.id')
            ->first();
         //END::VENTES

          //BEGIN::DEPENSES
          $summaryDepenses= DB::table('depenses as dp')
          ->select(
              DB::raw('COALESCE(CAST(SUM(dp.montant) AS INTEGER), 0) as sum_depense'),
              DB::raw('COUNT(dp.id) as total_depenses')
          )
          ->whereBetween('dp.created_at', [$start_date, $end_date])
          ->whereNotNull('dp.id')
          ->first();
          //END::DEPENSES

          //BEGIN::BENEFICES
          $benefices=0;
          $benefices=(int)$summaryOrders->total_sum-(int)$summaryDepenses->sum_depense;

          //END::BENEFICES

        // Préparation des données à renvoyer
        $donnees = [
            "date_debut" => Carbon::parse($start_date)->format('d/m/Y H:i:s'),
            "date_fin" => Carbon::parse($end_date)->format('d/m/Y H:i:s'),
            "sales"=>$summaryOrders,
            "depenses"=>$summaryDepenses,
            "benefices"=>$benefices,
            "produits" => $produits,
            "ventes_filter" => $ordersQuery,
            "produit_critique" => $produits_seuil_critique,
            "top_10_produits" => [
                'produits' => $produit_top,
                'total_vendu' => $totalVendu,
            ],
            "top_10_produits_par_valeur" => [
                'produits' => $produit_top_value,
                'total_valeur' => $totalValeur,
            ],
        ];

        // Retour des statistiques sous forme de réponse JSON
        return $this->sendResponse(true, "Liste des statistiques", $donnees);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
