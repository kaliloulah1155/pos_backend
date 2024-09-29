<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Licence;
use App\Models\Entreprise;
use Illuminate\Http\Request;
use App\Services\ImageService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class EntrepriseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

   /**
     *
     * @OA\Post (
     *     path="/entreprise",
     *     tags={"Entreprise"},
     *     summary="Formulaire de creation d'une entreprise",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="libelle",
     *                     type="string",
     *                     description="Saisir la raison sociale."
     *                 ),
     *                @OA\Property(
     *                     property="phone_1",
     *                     type="string",
     *                     description="N° telephone 1."
     *                 ),
     *                 @OA\Property(
     *                     property="phone_2",
     *                     type="string",
     *                     description="N° telephone 2."
     *                 ),
     *                 @OA\Property(
     *                     property="phone_fixe",
     *                     type="string",
     *                     description="N° telephone fixe."
     *                 ),
     *                 @OA\Property(
     *                     property="localisation",
     *                     type="string",
     *                     description="Localisation."
     *                 ),
     *                 @OA\Property(
     *                     property="license",
     *                     type="string",
     *                     description="Clé d'enregistrement(voir admin)."
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                      format="email",
     *                     description="E-mail de l'entreprise."
     *                 ),
     *                @OA\Property(
     *                     property="web",
     *                     type="string",
     *                     description="Site web de l'entreprise."
     *                 ),
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="Fichier image correspondant au logo de l'entreprise."
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Succès"
     *     )
     * )
     */

    public function store(Request $request)
    {
        try {
  
          

            $entreprise= Entreprise::updateOrCreate(
                ['license' => env('REGISTRATION_KEY')],
                [
                    "libelle"=> $request->libelle,
                    "phone_1"=>$request->phone_1,
                    "phone_2"=> $request->phone_2,
                    "phone_fixe"=> $request->phone_fixe,
                    "localisation"=> $request->localisation,
                    "email"=> $request->email,
                    "web"=> $request->web,
                    "created_user"=> Auth::id(),
                    "updated_user"=> Auth::id(),
                ]
            );

            //dd($request->hasFile('image'));

            if ($request->hasFile('image')) {
                (new ImageService)->updateImage($entreprise, $request, '/images/entreprise/', 'update');
                $entreprise->save();
            }

            return response()->json(['message' => "Entreprise crée avec succès"], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in EntrepriseController.store',
            ]);

        }
    }


     /**
     *
     * @OA\Post (
     *     path="/licence",
     *     tags={"Entreprise"},
     *     summary="Formulaire de creation d'une licence",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     description="Saisir la clé d'enregistrement."
     *                 ),
     *                @OA\Property(
     *                     property="dt_debut",
     *                     type="string",
     *                     format="date",
     *                     description="Date de debut.",
     *                     example="2024-01-01"
     *                 ),
     *                 @OA\Property(
     *                     property="dt_fin",
     *                     type="string",
     *                      format="date",
     *                     description="Date de fin.",
     *                     example="2024-01-01"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Succès"
     *     )
     * )
     */
    public function store_licence(Request $request)
    {
        try {

            $licence= Licence::updateOrCreate(
                ['code' => $request->code],
                [
                    "code"=> $request->code,
                    "dt_debut"=> $request->dt_debut,
                    "dt_fin"=>$request->dt_fin,
                    "status"=> 1,
                    "created_user"=> Auth::id(),
                    "updated_user"=> Auth::id(),
                ]
            );
 
            return response()->json(['message' => "Licence appliquée avec succès"], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in EntrepriseController.store_licence',
            ]);

        }
    }

    /**
     * @OA\Get (
     *     path="/entreprise/{id}",
     *     tags={"Entreprise"},
     *     summary="Affiche le détail de l'entreprise",
     *     description="Retourne les détails de l'entreprise",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *     )
     * )
     */
    public function show($id)
    {
        try{
            $mappedEntreprise=[];
            $data=Entreprise::where('id',$id)->get();


            if($data){

                $mappedEntreprise = $data->map(function ($dt) {

                    $registered = Licence::where("code", "=", $dt->license)->first();

                    $endDate = Carbon::parse($registered->dt_fin); // Convertit la date de fin en instance Carbon
                    $today = Carbon::now(); // Obtient la date et l'heure actuelles
                
                    // Comparer les dates
                    $etat_licence="Introuvable";
                    $val_licence=0;
                    if ($today->greaterThan($endDate)) {
                        $etat_licence= 'Expiré'; // Date actuelle est après la date de fin
                        $val_licence=1;
                    } else {
                        $etat_licence='Valide'; // Date actuelle est avant ou égale à la date de fin
                        $val_licence=2;
                    }

                    return[
                       'id' => $dt->id,
                       'libelle' => $dt->libelle,
                       'phone_1' => $dt->phone_1,
                       'phone_2' => $dt->phone_2,
                       'phone_fixe' => $dt->phone_fixe,
                       'localisation' => $dt->localisation,
                       'email' => $dt->email,
                       'web' => $dt->web,
                       'licence' => $dt->license,
                       'date_licence'=>$endDate->format('d/m/Y'),
                       'etat_licence'=>$etat_licence,
                       'val_licence'=>$val_licence,
                       'image' => $dt->image ? env('IMAGE_PATH_ENTREPRISE') . $dt->image : null,

                    ];
                });
                

                
            }
            return response()->json( $mappedEntreprise);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in EntrepriseController.show',
            ]);

        }
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
