<?php

namespace App\Http\Controllers;

 
use OpenApi\Annotations as OA;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

 
/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="GESTOCK CI",
 *     description="Projet de gestion des points de vente"
 * )
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Lien en local"
 * )
 * @OA\Server(
 *     url="https://pos789456123.kewoustore.com/api/v1",
 *     description="Lien de la recette"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

     /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($success=true,$message,$data=null)
    {
    	$response = [
            'error' => !$success,
            'message' => $message,
            'data'    => $data
        ];
        return response()->json($response, 200);
    }
}
