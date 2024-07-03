<?php 

namespace App\Services;
use App\Models\Action;
use App\Models\Permission;



class Serpermission{

    public function habilitation_user_action($profil_id,$menu_id){

        $dataActions = Action::where('statut','=',1)->orderBy('position','ASC')->get();
        $dataPermissions = Permission::where('profil_id','=',$profil_id)->where('menu_id','=',$menu_id)->get();
        
        $dataPermissions_ids = [];
        foreach ($dataPermissions as $permission) {
            $dataPermissions_ids[] = $permission['action_id'];
        }
       
        $resultat = [];
        foreach ($dataActions as $all_action) {
            $resultat[] = [
                $all_action["code"] => in_array($all_action["id"], $dataPermissions_ids) ? true : false
            ];
        }

        return response()->json($resultat);
        
    }
}