<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Salaire;
use App\Models\User;
use App\Services\AmountFormatService;
use App\Services\DateFormatService;
use App\Services\DateTranformService;
use App\Http\Requests\Salaire\StoreRequest;
use Illuminate\Support\Facades\Auth;
use DB;
use Carbon\Carbon;

class SalaireController extends Controller
{
    public function index(){
         try {
             
             $salaires = Salaire::get();
             
             
             // Map each user to include profile name
            $mappedSalaires = $salaires->map(function ($salaire) {
                $user = User::find($salaire->user_id);
                return [
                    'id' => $salaire->id,
                    'user_id' => $salaire->user_id,
                    'fullname' => $user ? $user->nom.' '.$user->prenoms : "NEANT",
                    'montant'=>(new AmountFormatService)->formatAmount($salaire->montant).' F CFA',
                    'date_salaire' =>(new DateFormatService)->formatToDDMMYYYY($salaire->date_salaire),
                ];
            });

            // Return the paginated data with additional information
            return response()->json([
                'data' => $mappedSalaires,
            ]);
             
         } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in SalaireController.index',
            ]);
        }
    }
    
     /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        
        try {
            $salaire = Salaire::create([
                'user_id' => $request->user_id,
                'montant' => $request->montant ?? 0,
                'date_salaire' => (new DateTranformService )->transformToYYYYMMDD($request->date_salaire) ??  date('Y-m-d'),
                'created_user' => Auth::id(),
            ]);
            return response()->json($salaire, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in SalaireController.store',
            ]);
        }
    }
    
    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {

            $salaire = Salaire::find($id);
            if (!$salaire) {
                return response()->json([
                    'success' => false,
                    'message' => "Salaire introuvable",
                ]);
            }
            
              $user = User::find($salaire->user_id);
              
             $salaire['fullname'] = $user ? $user->nom.' '.$user->prenoms : "NEANT";
            $salaire['montant']=(new AmountFormatService)->formatAmount($salaire->montant).' F CFA'; 
            $salaire['date_salaire']=(new DateFormatService )->formatToDDMMYYYY($salaire->date_salaire); 
            return response()->json([
                'success' => true,
                'salaire' => $salaire,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in SalaireController.show',
            ]);
        }
    }
    
     /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        try {

            $salaire= Salaire::find($id);
            if (!$salaire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Salaire avec l\'id ' . $id . ' n\'existe pas!',
                ], 200);
            }
            $salaire->user_id = $request->user_id ?? $salaire->user_id;
            $salaire->montant = $request->montant ?? $salaire->montant;
            $salaire->date_salaire = (new DateTranformService )->transformToYYYYMMDD($request->date_salaire) ?? $salaire->date_salaire;
            $salaire->updated_user = Auth::id();
            $salaire->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Salaire avec l\'id ' . $id . ' a été mis à jour!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in SalaireController.update',
            ]);
        }

    }
    
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        try {

            $record = Salaire::where('id', $id)->get();
            $current = Carbon::now();
            if (count($record) > 0) {
                Salaire::where('id', $id)->update([
                    'deleted_at' => $current,
                    'deleted_user' => Auth::id(),
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Salaire avec l\'id ' . $id . ' a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "Ce salaire n'existe pas",
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in SalaireController.destroy',
            ]);
        }

    }

    public function delete(int $id)
    {
        try {
            $record = Salaire::where('id', $id)->get();
            if (count($record)) {
                DB::table('salaires')->where('id', $id)->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Salaire avec l\'id ' . $id . ' a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "Le salaire n'existe pas",
                ]);
            }
        } catch (\Exception$e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in SalaireController.delete',
            ]);
        }
    }
}
