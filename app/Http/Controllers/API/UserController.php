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
                    //'image' => $user->image ? env('IMAGE_PATH_USERS').$user->image : null,
                     'image' => $user->image ? env('IMAGE_PATH_USERS').Storage::url('users/'.$user->image) : null,
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
     * Liste des clients
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
                    'image' => $user->image ? env('IMAGE_PATH_USERS').Storage::url('users/'.$user->image) : null,
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
                    'image' => $user->image ? env('IMAGE_PATH_USERS').Storage::url('users/'.$user->image) : null,
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
