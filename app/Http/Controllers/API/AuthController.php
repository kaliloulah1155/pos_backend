<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Menu;
use App\Models\User;
use App\Models\Profil;
use App\Models\Licence;
use App\Models\Entreprise;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use Illuminate\Http\Response;
use App\Services\ImageService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Notifications\PasswordResetNotification;
use App\Http\Requests\ForgotPassword\ResetPasswordRequest;
use App\Http\Requests\ForgotPassword\ForgotPasswordRequest;

class AuthController extends Controller
{

    public function register(RegisterRequest $request)
    {
        try {
            $user = User::create([
                'nom' => $request->nom,
                'prenoms' => $request->prenoms,
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'email' => $request->email,
                'sexe' => $request->sexe=="M" ? "M":"F",
                'isActive'=>$request->isActive,
                'profil_id' => $request->profil_id,
                'password' => Hash::make($request->password),
            ]);
            if ($request->hasFile('image')) {
                (new ImageService)->updateImage($user, $request, '/images/users/', 'update');
                $user->save();
            }

            $token = $user->createToken('user_token')->plainTextToken;

            return response()->json(['user' => $user, 'token' => $token], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in AuthController.register',
            ]);
        }
    }


     /**
     * Display a listing of the resource.
     */
    /**
     *
     * @OA\Post (
     *     path="/login",
     *     tags={"Authentifications"},
     *     summary="Connexion d'un utilisateur",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"login", "password"},
     *                 @OA\Property(
     *                     property="login",
     *                     type="string",
     *                     example="login",
     *                      description="E-mail ou N° de téléphone"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     example="password"
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
    public function login(LoginRequest $request)
    {
        try {
           
            //0173832778
            //ibrahim1155@outlook.com
            $emailOrPhoneNumber = $request->login;
            
            $user = User::where(function ($query) use ($emailOrPhoneNumber) {
                $query->where('email', utf8_encode($emailOrPhoneNumber))
                    ->orWhere('telephone', utf8_encode($emailOrPhoneNumber));
            })->first();
           
            $deletedRecords = User::withTrashed()
                ->whereNotNull('deleted_at')
                ->where(function ($query) use ($emailOrPhoneNumber) {
                    $query->where('email', $emailOrPhoneNumber)
                        ->orWhere('telephone', $emailOrPhoneNumber);
                })->first();

            if ($deletedRecords) {
                return response()->json([
                    'error' => true,
                    'message' => "Votre compte a été supprimer de la plateforme veuillez contacter l'administrateur.",
                ]);
            }

            if (!$user) {
                return response()->json([
                    'error' => true,
                    'message' => 'Les informations de connexion sont invalides. Veuillez vérifier votre login et votre mot de passe et réessayer.',
                ]);
            }

            if ($user->isActive == 0) {
                return response()->json([
                    'error' => true,
                    'message' => "Votre compte est inactif. Veuillez contacter l'administrateur pour plus d'informations.",
                ]);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'error' => true,
                    'message' => "Votre mot de passe est incorrect. Veuillez le vérifier.",
                ]);
            }

            //Controle de la licence
            $registered = Licence::where("code", "=", env('REGISTRATION_KEY'))->first();

            if(!$registered){
                return response()->json([
                    'error' => true,
                    'message' => "Vous n'avez pas de licence.",
                ]);
            }
            $endDate = Carbon::parse($registered->dt_fin); // Convertit la date de fin en instance Carbon
            $today = Carbon::now();

            $etat_licence=0;
            if ($today->greaterThan($endDate)) {
                $etat_licence= 1; // licence expiré
            }else{
                $etat_licence=2; // licence valide
            }  

            if($etat_licence==0){
                return response()->json([
                    'error' => true,
                    'message' => "Vous n'avez pas de licence.",
                ]);
            }
            if($etat_licence==1){
                return response()->json([
                    'error' => true,
                    'message' => "Votre licence a expiré le ".$endDate->format('d/m/Y'),
                ]);
            }


            $token = $user->createToken('user_token')->plainTextToken;

            return response()->json(['token' => $token], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in AuthController.login',
            ]);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $user->tokens()->delete();
            return response()->json(['user' => 'User logged out!'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in AuthController.logout',
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        //
    }


     /**
     *
     * @OA\Post (
     *     path="/forgot",
     *     tags={"Authentifications"},
     *     summary="Mot de passe oublié",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"email"},
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                      description="E-mail"
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
    public function forgot(Request $request)
    {

        $user = ($query = User::query());

        $user = $user->where($query->qualifyColumn('email'), $request->email)->first();

        $resetPasswordToken = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        if (!$userPassReset = PasswordReset::where('email', $user->email)->first()) {

            PasswordReset::create([
                'email' => $user->email,
                'token' => $resetPasswordToken,
            ]);
        } else {
            PasswordReset::where('email', $user->email)->update([
                'email' => $user->email,
                'token' => $resetPasswordToken,
            ]);
        }

        //send notification
        $user['resetPasswordToken'] = $resetPasswordToken;
        $user->notify(

            new PasswordResetNotification($user)
        );
       

        return response()->json([
            'message' => "Un code vous a été envoyé sur votre adresse email",
        ]);

    }



     /**
     *
     * @OA\Post (
     *     path="/resetpwd",
     *     tags={"Authentifications"},
     *     summary="Modification du mot de passe oublié",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"token","password"},
     *                 @OA\Property(
     *                     property="token",
     *                     type="integer",
     *                      description="Code OTP recu par e-mail"
     *                 ),
     *                  @OA\Property(
     *                     property="password",
     *                     type="string",
     *                      description="Nouveau mot de passe"
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

    public function resetpwd(Request $request)
    {
       

        $resetRequest = PasswordReset::where('token', $request->token)->first();

        if (!$resetRequest) {
            return response()->json([
                'error' => true,
                'message' => "Votre code est incorrecte",
            ]);
        }

        $user = User::where('email', $resetRequest['email'])->first();

        $user->fill([
            'password' => Hash::make($request['password']),
        ]);
        $user->save();
        //delete previous token
        $user->tokens()->delete();

        PasswordReset::where('token', $request->token)->delete();

        $token = $user->createToken('user_token')->plainTextToken;

        $loginResponse = [
            'user' => $user,
            'token' => $token,
        ];
        return response()->json([
            'data' => $loginResponse,
            'message' => "Mot de passe réinitialisez avec succès",

        ], 201);

    }
    
     /**
     * @OA\Get(
     *     path="/infoUser",
     *     tags={"Authentifications"},
     *      summary="Récupération des infos de l'utilisateur connecté",
     *      description="Retourne tous les infos de l' utilisateurs",
     *      @OA\Response(response=200,description="succès"),
     *      @OA\Response(response=401, description="Token expiré | Token invalide | Token absent "),
     *      @OA\Response(response=404, description="Ressource introuvable"),
     *       security={{"sanctum":{}}}  
     * ),
     */
    public function getUserInfo(Request $request)
    {
        try {
            $user = $request->user();
            
            $id=$user['profil_id'];

            $entreprise = Entreprise::where("license", "=", env('REGISTRATION_KEY'))->first();
            if ($entreprise) {
                $entreprise->image = $entreprise->image ? env('IMAGE_PATH_ENTREPRISE') . $entreprise->image : null;
            }
            
            //GESTION DES MENUS
            
             $profil = DB::table('profils')
                ->select('profils.*')
                ->where('profils.id', $id)
                ->get();

            $menusPerPage = 100;

           
            $dataMenus = DB::table('menus')
            ->select('menus.*', 'permissions.*')
            ->leftJoin('permissions', 'menus.id', '=', 'permissions.menu_id')
            ->where('menus.statut', 1)
            ->whereNull('menus.deleted_at')
            ->where('permissions.profil_id', $id)
            ->orderBy('menus.position', 'ASC')
             ->simplePaginate($menusPerPage);

             
                
            $pageCount = count(Menu::all()) / $menusPerPage;

           
            $dataActions = DB::table('actions')
                ->select('actions.*')
                ->distinct('actions.id')
                ->where('actions.statut', 1)
                ->orderBy('actions.position', 'ASC')
                ->get();
            $result = [];
            $data = [];
            $dataPermissions = DB::table('permissions')
                ->select('permissions.*')
                ->where('permissions.profil_id', $id)
                ->get();

            if (count($profil) > 0) {

                $dataPermissions_ids = [];
                foreach ($dataPermissions as $permission) {
                    if (isset($permission->menu_id) and isset($permission->action_id)) {
                        $dataPermissions_ids[] = [$permission->menu_id, $permission->action_id];
                    }
                }
                foreach ($dataMenus as $dataMenu) {
                    $id_menu = $dataMenu->id;
                    $lib_menu_lib = $dataMenu->libelle;
                    $lib_menu_icon = $dataMenu->icon;
                    $lib_menu_path = $dataMenu->target;
                    $res_action = [];
                    foreach ($dataActions as $all_action) {
                        $perm = false;
                        if (in_array(array($id_menu, $all_action->id), $dataPermissions_ids)) {
                            $perm = true;
                        }

                        $res_action[] = [
                            'action_id' => $all_action->id,
                            'action_lib' => $all_action->libelle,
                            'action_code' => $all_action->code,
                            'habilitation' => $perm,
                        ];

                    }
                    $result[] = [
                        'resourceId' => $id_menu,
                        'resourceName' => $lib_menu_lib,
                        'resourceIcon' => $lib_menu_icon,
                        'resourcePath' => $lib_menu_path,
                        'page_count' => ceil($pageCount),
                        //'permissions' => array_diff_key($res_action),
                    ];
                }       
            }
            
            //FIN GESTION DES MENUS
            if ($user) {

                $user['image'] = $user->image == null ? $user->image : env('IMAGE_PATH_USERS').$user->image;

                $user['profile_name'] = Profil::where('id', $user->profil_id)->value('libelle');
                $user['profile_code'] = Profil::where('id', $user->profil_id)->value('code');

                unset($entreprise->license); // Masquer la licence 
                $user["entreprise"]= $entreprise;

                $user['token'] = $request->bearerToken();
                $user['menus'] =  $result;
                 
                return response()->json($user);
            } else {
                return response()->json(['error' => 'User not found'], 404);
            }
        } catch (\Exception $e) {

            return response()->json(['error' => 'An error occurred'], 500);
        }
    }
}
