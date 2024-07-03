<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\StoreRequest;
use App\Models\Menu;
use App\Models\Permission;
use App\Services\Serpermission;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PermissionController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        try {

            
            $this->destroy($request->profil_id);

            $data_perm = [];

            foreach ($request->choices as $data=> $checked) {
                $arr_splt = explode("-", $data);

               

                $menu_id = (int) $arr_splt[0];
                $action_id = (int) $arr_splt[1];

                if($checked==true){
                        $data_perm[] = [
                            'menu_id' => $menu_id,
                            'profil_id' => $request->profil_id,
                            'action_id' => $action_id,
                            'created_user' => Auth::id(),
                            'created_at' => Carbon::now(),
                        ];
               }
            }

            DB::table('permissions')->insert($data_perm);

            return response()->json([
                'result' => true,
                "message" => "succès",
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in PermissionController.store',
            ]);
        }

    }
    /**
     * Display the specified resource.
     */
    public function show(int $id)
    { // passage id profil {
        try {

            $profil = DB::table('profils')
                ->select('profils.*')
                ->where('profils.id', $id)
                ->get();

            $menusPerPage = 100;

            $dataMenus = DB::table('menus')
                ->select('menus.*')
                ->where('menus.statut', 1)
                ->where('menus.deleted_at', null)
                ->orderBy('menus.position', 'ASC')
                ->simplePaginate($menusPerPage);
            $pageCount = count(Menu::all()) / $menusPerPage;

            /*    dd( $dataMenus);
            return response()->json([
            'paginate' => $dataMenus,
            'page_count' => ceil($pageCount),
            ], 200);
            exit;
             */
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
                        'page_count' => ceil($pageCount),
                        'permissions' => array_diff_key($res_action),
                    ];
                }

                $data[] = [
                    'id_profil' => $profil[0]->id,
                    'libelle' => $profil[0]->libelle,
                    'menus' => $result,
                ];

                return response()->json($data, 201);

            } else {
                return response()->json([
                    'result' => false,
                    "message" => "Ce profil n'existe pas",
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in PermissionController.show',
            ]);
        }
    }

/**
 * Remove the specified resource from storage.
 */
    public function destroy(int $id)
    { // passage id profil {
        try {
            $record = Permission::where('profil_id', $id)->get();
            if (count($record)) {
                DB::table('permissions')->where('profil_id', $id)->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Permission a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "Permission n'existe pas",
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in PermissionController.delete',
            ]);
        }

    }

    public function menu_profil(int $profil)
    {
        try {

            $dataMenus = DB::table('permissions')
                ->leftJoin('menus', 'menus.id', '=', 'permissions.menu_id')
                ->select('menus.*')
                ->where('permissions.profil_id', $profil)
                ->where('menus.menu_id', '=', null)
                ->distinct()
                ->get();
            $dataActions = DB::table('actions')
                ->select('actions.*')
                ->distinct('actions.id')
                ->where('actions.statut', 1)
                ->where('actions.code', '=', 'show')
                ->get();

            $dataPermissions = DB::table('permissions')
                ->select('permissions.*')
                ->where('permissions.profil_id', $profil)
                ->get();

            $result = [];
            $res_ss_menus = [];

            $dataPermissions_ids = [];
            foreach ($dataPermissions as $permission) {
                if (isset($permission->menu_id) and isset($permission->action_id)) {
                    $dataPermissions_ids[] = [$permission->menu_id, $permission->action_id];
                }
            }

            //  dump($dataPermissions_ids);

            foreach ($dataMenus as $dataMenu) {

                $data_ss_Menus = DB::table('permissions')
                    ->leftJoin('menus', 'menus.id', '=', 'permissions.menu_id')
                    ->select('menus.*')
                    ->where('permissions.profil_id', $profil)
                    ->where('menus.menu_id', '=', $dataMenu->id)
                    ->distinct()
                    ->get();
                $perm_menu_parent = false;
                foreach ($dataActions as $all_action) {
                    if (in_array(array($dataMenu->id, $all_action->id), $dataPermissions_ids)) {
                        $perm_menu_parent = true;
                    }
                }

                $res_ss_menus = [];

                // dump($data_ss_Menus);
                foreach ($data_ss_Menus as $data_ss_Menu) {
                    $perm_ss_menu = false;
                    foreach ($dataActions as $all_action) {
                        if (in_array(array($data_ss_Menu->id, $all_action->id), $dataPermissions_ids)) {
                            $perm_ss_menu = true;
                        }
                    }
                    if ($perm_ss_menu) {
                        $res_ss_menus[] = [
                            'icon' => $data_ss_Menu->icon ?? '',
                            'pageName' => $data_ss_Menu->name ?? "",
                            'title' => $data_ss_Menu->libelle,
                        ];
                    }

                }
                if ($perm_menu_parent) {
                    $result[] = [
                        'icon' => $dataMenu->icon ?? 'HomeIcon',
                        'pageName' => $dataMenu->name ?? "",
                        'title' => $dataMenu->libelle,
                        'subMenu' => array_diff_key($res_ss_menus),
                    ];
                }
            }

            return response()->json($result, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in PermissionController.menu_profil',
            ]);
        }
    }

    public function test_permission(Request $request)
    {
        $id_profil = $request->profil_id;
        $menu_libelle = $request->menu_libelle;

        $rec_menu = Menu::where('libelle', $menu_libelle)->get();
        $id_menu = $rec_menu[0]->id;
        return (new Serpermission)->habilitation_user_action($id_profil, $id_menu);
    }

}
