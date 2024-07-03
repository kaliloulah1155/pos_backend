<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Class StoreMedia.
 */
class StoreMedia
{
    public function imageStore($file, $filename, $filetable, $table, $userId, $folder, $slug = null)
    {

        // Construire le chemin du dossier en incluant l'ID de l'utilisateur
        $folderPath = 'public/media/' . $folder . '/' . $userId. '/' .$filetable;

        // Créer le dossier avec les permissions spécifiques (par exemple 0755)
        Storage::makeDirectory($folderPath, 0755, true);

        // Stocker le fichier dans le dossier avec le nom d'origine
        Storage::putFileAs(
            $folderPath,
            $file,
            $filename
        );

        // Créer un nouvel objet Media
        $media =[];
        $media['table'] = $table;
        // Utiliser le chemin du dossier avec l'ID de l'utilisateur
        $media['lien'] = "/storage/media/{$folder}/{$userId}/{$filetable}/" . $filename;
        $media['format'] = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $media['libelle'] = $filename;
        $media['table_id'] = $filetable;
        $media['status'] = 1;
        $media['created_user'] = $userId;
        $media['slug'] = $slug;
       

     Media::updateOrCreate(
        [
            'table_id' => $filetable,
              'slug' => $slug,
              'table'=>$table
        ], $media);
      
    }
}
