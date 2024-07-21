<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    
   /**
 *
 * @OA\Get (
 *     path="/orders",
 *     tags={"Commandes"},
 *     summary="Liste des commandes | start_date et end_date ne sont pas obligatoires",
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

    public function index(Request $request)
    { $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
    
        // Construction de la requête principale pour les commandes
        $ordersQuery = DB::table('users as clt')
            ->select(
                'ps.created_at',
                'ps.id as order_id', 'clt.id as client_id', 'clt.nom as client_nom', 'clt.prenoms as client_prenoms',
                'cre.id as createur_id', 'cre.nom as createur_nom', 'cre.prenoms as createur_prenoms',
                'cat.id as methodpaid_id', 'cat.libelle as methodpaid',
                'ps.transaction_id', 'ps.tva', 'ps.remise', 'ps.espece', 'ps.monnaie', 'ps.qte_total as order_amount', 'ps.print_status as printed', 'ps.status'
            )
            ->leftJoin('pos as ps', 'clt.id', '=', 'ps.client_id')
            ->leftJoin('users as cre', 'ps.created_user', '=', 'cre.id')
            ->leftJoin('categories as cat', 'ps.paid_method_id', '=', 'cat.id')
            ->whereNotNull('ps.id');
    
        // Appliquer les filtres de date si disponibles
        if ($startDate) {
            $ordersQuery->where('ps.created_at', '>=', $startDate);
        }
    
        if ($endDate) {
            $ordersQuery->where('ps.created_at', '<=', $endDate);
        }
    
        $ordersQuery->orderBy('ps.created_at', 'desc');
    
        $orders = $ordersQuery->get();
    
        // Calculer le cumul global en somme (ps.qte_total) et le nombre de transactions
        $summaryQuery = DB::table('pos as ps')
            ->select(
                DB::raw('SUM(ps.qte_total) as total_sum'),
                DB::raw('COUNT(ps.id) as total_transactions')
            )
            ->whereNotNull('ps.id');
    
        // Appliquer les filtres de date si disponibles
        if ($startDate) {
            $summaryQuery->where('ps.created_at', '>=', $startDate);
        }
    
        if ($endDate) {
            $summaryQuery->where('ps.created_at', '<=', $endDate);
        }
        $summary = $summaryQuery->first();

        // Calculer le cumul global en somme (ps.qte_total) et le nombre de transactions d'aujourd'hui
        $summaryQueryToday = DB::table('pos as ps')
            ->select(
                DB::raw('SUM(ps.qte_total) as total_sum'),
                DB::raw('COUNT(ps.id) as total_transactions')
            )
            ->whereNotNull('ps.id')
            ->where('ps.created_at','>=',Carbon::today()->startOfDay())
            ->where('ps.created_at', '<=', Carbon::today()->endOfDay());
            $summaryToday = $summaryQueryToday->first();
    
        $donnees=[
            'success' => true,
            'message' => 'Liste des commandes',
            'data' => [
                'orders' => $orders,
                'summary' => $summary,
                'summaryToday'=>$summaryToday
            ],
        ];
        
        return $this->sendResponse(true, "Liste des commandes", $donnees);
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
