<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Depense;
use App\Services\AmountFormatService;
use App\Services\DateFormatService;
use App\Services\DateTranformService;
use App\Http\Requests\Depense\StoreRequest;
use Illuminate\Support\Facades\Auth;
use DB;
use Carbon\Carbon;

class DepenseController extends Controller
{
    public function index(){
         try {
             
             $depenses = Depense::get();
             
             
             // Map each user to include profile name  
            $mappedDepenses = $depenses->map(function ($depense) {
                
                return [
                    'id' => $depense->id,
                    'description' => $depense->description,
                    'montant'=>(new AmountFormatService)->formatAmount($depense->montant).' F CFA',
                    'date_depense' =>(new DateFormatService)->formatToDDMMYYYY($depense->date_depense),
                ];
            });

            // Return the paginated data with additional information
            return response()->json([
                'data' => $mappedDepenses,
            ]);
             
         } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in DepenseController.index',
            ]);
        }
    }
    
     /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        
        try {
            $depense = Depense::create([
                'description' => $request->description,
                'montant' => $request->montant ?? 0,
                //'date_depense' => (new DateTranformService )->transformToYYYYMMDD($request->date_depense) ??  date('Y-m-d'),
                'date_depense' =>$request->date_depense,
                'created_user' => Auth::id(),
            ]);
            return response()->json($depense, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in DepenseController.store',
            ]);
        }
    }
    
    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {

            $depense = Depense::find($id);
            if (!$depense) {
                return response()->json([
                    'success' => false,
                    'message' => "Depense introuvable",
                ]);
            }
            $depense['montant']=(new AmountFormatService)->formatAmount($depense->montant).' F CFA'; 
            $depense['date_depense']=(new DateFormatService )->formatToDDMMYYYY($depense->date_depense); 
            return response()->json([
                'success' => true,
                'depense' => $depense,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in DepenseController.show',
            ]);
        }
    }
    
     /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        try {

            $depense= Depense::find($id);
            if (!$depense) {
                return response()->json([
                    'success' => false,
                    'message' => 'Depense avec l\'id ' . $id . ' n\'existe pas!',
                ], 200);
            }
            $depense->description = $request->description ?? $depense->description;
            $depense->montant = $request->montant ?? $depense->montant;
            $depense->date_depense = (new DateTranformService )->transformToYYYYMMDD($request->date_depense) ?? $depense->date_depense;
            $depense->updated_user = Auth::id();
            $depense->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Depense avec l\'id ' . $id . ' a été mis à jour!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in DepenseController.update',
            ]);
        }

    }
    
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        try {

            $record = Depense::where('id', $id)->get();
            $current = Carbon::now();
            if (count($record) > 0) {
                Depense::where('id', $id)->update([
                    'deleted_at' => $current,
                    'deleted_user' => Auth::id(),
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Depense avec l\'id ' . $id . ' a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "Cette depense n'existe pas",
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in DepenseController.destroy',
            ]);
        }

    }

    public function delete(int $id)
    {
        try {
            $record = Depense::where('id', $id)->get();
            if (count($record)) {
                DB::table('depenses')->where('id', $id)->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Depense avec l\'id ' . $id . ' a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "La depense n'existe pas",
                ]);
            }
        } catch (\Exception$e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in DepenseController.delete',
            ]);
        }
    }
    
    
}
