<?php

namespace App\Services;

/**
 * Class PhotoService.
 */
class PhotoService
{
   public function lire($filename){
        $path = storage_path('app/public/users/' . $filename);
        if(!file_exists($path)) abort(404);
        $file = file_get_contents($path);
        $type = mime_content_type($path);
        return response($file, 200)->header("Content-Type", $type);
   }
}
