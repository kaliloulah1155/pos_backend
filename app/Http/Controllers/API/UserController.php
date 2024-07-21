<?php

namespace App\Http\Controllers\API;

use DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Profil;
use Illuminate\Http\Response;
use App\Services\ImageService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\User\UpdateRequest;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {

            $users = User::get();

            // Map each user to include profile name
            $mappedUsers = $users->map(function ($user) {
                $lib_active = "Désactivé";
                if ($user->isActive == 1) {
                    $lib_active = "Activé";
                }
                return [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenoms' => $user->prenoms,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                    'image' => $user->image ,
                    'lib_active' => $lib_active,
                    'profile_name' => $user->profil_id !=null ?  Profil::find($user->profil_id)->libelle :null,

                    // Add more properties if needed
                ];
            });

            // Return the paginated data with additional information
            return response()->json([
                'data' => $mappedUsers,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in UserController.index',
            ]);

        }
    }

    /**
     * Liste des employes
     */
    public function employes()
    {
        try {
           
            $users = DB::table('users')
                ->join('profils', 'users.profil_id', '=', 'profils.id')
                ->whereNotIn('profils.libelle', ['Client', 'Fournisseur'])
                ->where('users.deleted_at','=',null)
                ->select('users.*')
                ->get();
                 
                
            $mappedUsers = $users->map(function ($user) {
                $lib_active = "Désactivé";
                if ($user->isActive == 1) {
                    $lib_active = "Activé";
                }
                 
                 return [
                    'id' => $user->id,
                    'fullname' => $user->nom . ' ' . $user->prenoms,
                    'nom' => $user->nom,
                    'prenoms' => $user->prenoms,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                    'sexe' => $user->sexe,
                    'lib_sexe' => $user->sexe == "M" ? "Homme" : "Femme",
                    'image' => $user->image ? env('IMAGE_PATH_USERS').$user->image : null,
            
                    'statut' => $user->isActive,
                    'lib_active' => $lib_active,
                    'profil_id'=>$user->profil_id,
                   'profile_name' => $user->profil_id !=null ?  Profil::find($user->profil_id)->libelle :null,


                    // Add more properties if needed
                ]; 
            });
         
            return response()->json([
                'data' => $mappedUsers,
            ]); 

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in UserController.employes',
            ]);

        }
    }
     /**
     * @OA\Get(
     *     path="/clients",
     *     tags={"Clients"},
     *      summary="Récupération de la liste des clients",
     *      description="Retourne toute la liste des clients",
     *      @OA\Response(response=200,description="succès"),
     *      @OA\Response(response=401, description="Token expiré | Token invalide | Token absent "),
     *      @OA\Response(response=404, description="Ressource introuvable"),
     *       security={{"sanctum":{}}}  
     * ),
     */
    public function clients()
    {
        try {
            $users = DB::table('users')
                ->join('profils', 'users.profil_id', '=', 'profils.id')
                ->whereIn('profils.libelle', ['Client'])
                ->where('users.deleted_at','=',null)
                ->select('users.*')
                ->get();
            $mappedUsers = $users->map(function ($user) {
                $lib_active = "Désactivé";
                if ($user->isActive == 1) {
                    $lib_active = "Activé";
                }
                return [
                    'id' => $user->id,
                    'fullname' => $user->nom . ' ' . $user->prenoms,
                    'nom' => $user->nom,
                    'prenoms' => $user->prenoms,
                    'adresse' => $user->adresse,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                     'sexe' => $user->sexe,
                    'lib_sexe' => $user->sexe == "M" ? "Homme" : "Femme",
                    'image' => $user->image ? env('IMAGE_PATH_USERS').$user->image : null,
                    'statut' => $user->isActive,
                    'lib_active' => $lib_active,
                    'profil_id'=>$user->profil_id,
                    'profile_name' => $user->profil_id !=null ?  Profil::find($user->profil_id)->libelle :null,

                    // Add more properties if needed
                ];
            });

            return response()->json([
                'data' => $mappedUsers,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in UserController.clients',
            ]);

        }
    }
    /**
     * Liste des fournisseurs 
     */
      /**
     * @OA\Get(
     *     path="/fournisseurs",
     *     tags={"Fournisseurs"},
     *      summary="Récupération de la liste des fournisseurs",
     *      description="Retourne toute la liste des fournisseurs",
     *      @OA\Response(response=200,description="succès"),
     *      @OA\Response(response=401, description="Token expiré | Token invalide | Token absent "),
     *      @OA\Response(response=404, description="Ressource introuvable"),
     *       security={{"sanctum":{}}}  
     * ),
     */
    public function fournisseurs()
    {
        try {
            $users = DB::table('users')
                ->join('profils', 'users.profil_id', '=', 'profils.id')
                ->whereIn('profils.libelle', ['Fournisseur'])
                ->where('users.deleted_at','=',null)
                ->select('users.*')
                ->get();
            $mappedUsers = $users->map(function ($user) {
                $lib_active = "Désactivé";
                if ($user->isActive == 1) {
                    $lib_active = "Activé";
                }
                return [
                    'id' => $user->id,
                    'fullname' => $user->nom . ' ' . $user->prenoms,
                    'nom' => $user->nom,
                    'prenoms' => $user->prenoms,
                    'adresse' => $user->adresse,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                     'sexe' => $user->sexe,
                    'lib_sexe' => $user->sexe == "M" ? "Homme" : "Femme",
                    'image' => $user->image ? env('IMAGE_PATH_USERS').$user->image : null,
                    'statut' => $user->isActive,
                    'lib_active' => $lib_active,
                    'profil_id'=>$user->profil_id,
                    'profile_name' => $user->profil_id !=null ?  Profil::find($user->profil_id)->libelle :null,
                    // Add more properties if needed
                ];
            });

            return response()->json([
                'data' => $mappedUsers,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in UserController.fournisseurs',
            ]);

        }
    }

    /**
     * Display the specified resource.
     */
      /**
     * @OA\Get(
     *     path="/users/{id}",
     *     tags={"Utilisateurs"},
     *      summary="Récupération de la liste des utilisateurs",
     *      description="Retourne toute la liste des utilisateurs",
     *      @OA\Parameter(
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *      @OA\Response(response=200,description="succès"),
     *      @OA\Response(response=401, description="Token expiré | Token invalide | Token absent "),
     *      @OA\Response(response=404, description="Ressource introuvable"),
     *       security={{"sanctum":{}}}  
     * ),
     */
    public function show(int $id)
    {
        try {

            $user = User::join('profils', 'profils.id', '=', 'users.profil_id')
                ->select('users.*', 'profils.id as profil_id', 'profils.libelle as profil_lib', 'profils.description as profil_description')
                ->where('users.id', $id)
                ->where('profils.statut', 1)
                ->get();

            if (!$user) {
                return response()->json(['success' => false, 'message' => "Cet utilisateur n'existe pas"], 200);
            }

            return response()->json($user, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in UserController.show',
            ]);
        }
    }

    /**
     * @OA\Post (
     *     path="/users/{id}",
     *     tags={"Utilisateurs"},
     *     summary="Mise à jour d'un utilisateur",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         description="L'identifiant de l'utilisateur",
     *         required=true,
     *         in="path",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="nom",
     *                     type="string",
     *                     description="Nom de l'utilisateur."
     *                 ),
     *                 @OA\Property(
     *                     property="prenoms",
     *                     type="string",
     *                     description="Prénom de l'utilisateur."
     *                 ),
     *                 @OA\Property(
     *                     property="adresse",
     *                     type="string",
     *                     description="Adresse de l'utilisateur."
     *                 ),
     *                 @OA\Property(
     *                     property="telephone",
     *                     type="string",
     *                     description="N° de téléphone de l'utilisateur"
     *                 ),
     *                 @OA\Property(
     *                     property="isAdmin",
     *                     type="integer",
     *                     description="1=> true | 0 => false."
     *                 ),
     *                 @OA\Property(
     *                     property="isActive",
     *                     type="integer",
     *                     description="1=> true | 0 => false."
     *                 ),
     *                 @OA\Property(
     *                     property="sexe",
     *                     type="string",
     *                     description="Genre de l'utilisateur M=>Masculin | F=>Féminin."
     *                 ),
     *                  @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     description="E-mail de l'utilisateur."
     *                 ),
     *                @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     description="Mot de passe de l'utilisateur."
     *                 ),
     *                   @OA\Property(
     *                     property="password_confirmation",
     *                     type="string",
     *                     description="Confirmation du mot de passe de l'utilisateur."
     *                 ),
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="Charger votre photo."
     *                 ),
     *                  @OA\Property(
     *                     property="profil_id",
     *                     type="integer",
     *                     description="Voir l'identifiant du profil(table profil)."
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success"
     *     )
     * )
     */
    public function update(UpdateRequest $request, int $id)
    {
        try {

            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur avec l\'id ' . $id . ' n\'existe pas!',
                ], 200);
            }

            if ($request->hasFile('image')) {

                (new ImageService)->updateImage($user, $request, '/images/users/', 'update');
            }
            $user->nom = $request->nom ?? $user->nom;
            $user->prenoms = $request->prenoms ?? $user->prenoms;
            $user->adresse = $request->adresse ?? $user->adresse;
            $user->telephone = $request->telephone ?? $user->telephone;
            $user->isAdmin = $request->isAdmin ?? $user->isAdmin;
            $user->isActive = $request->isActive ?? $user->isActive;
            $user->sexe = $request->sexe == "M" ? "M" : "F";
            $user->email = $request->email ?? $user->email;
            $user->profil_id = $request->profil_id ?? $user->profil_id;
            $user->updated_user = Auth::id();

            if (isset($request->password)) {
                $this->validate($request, [
                    'password' => 'min:8|confirmed',
                ],
                    [
                        'password.min' => "Le champ du mot de passe doit contenir au moins 6 caractères.",
                        'password.confirmed' => 'Le mot de passe ne correspond pas à la confirmation.',
                    ]
                );
                $user->password = Hash::make($request->password);
            }

            $user->save();
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur avec l\'id ' . $id . ' a été mis à jour!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in UserController.update',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {

        try {
            $record = User::where('id', $id)->get();
            $current = Carbon::now();

            if (intval($record[0]->profil_id) != 1) {
                if (count($record) > 0) {
                    User::where('id', $id)->update([
                        'deleted_at' => $current,
                        'deleted_user' => Auth::id(),
                    ]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Utilisateur avec l\'id ' . $id . ' a été supprimé!']
                        , 201);
                } else {
                    return response()->json([
                        'result' => false,
                        "message" => "Cet Utilisateur n'existe pas",
                    ]);
                }
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in UserController.destroy',
            ]);
        }

    }

    public function delete(int $id)
    {
        try {
            $record = User::where('id', $id)->get();

            if (count($record)) {
                DB::table('users')->where('id', $id)->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Utilisateur avec l\'id ' . $id . ' a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "Cet Utilisateur n'existe pas",
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in UserController.delete',
            ]);
        }
    }

}
