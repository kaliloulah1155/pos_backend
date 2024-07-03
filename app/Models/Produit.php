<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Categorie;
use App\Models\User;


class Produit extends Model 
{
    use HasFactory,SoftDeletes;
    
     protected $guarded = [];
     
    
     
       //Code 3 char
   public static function formatChaine($chaine)
    {
        $troisPremiers = mb_substr($chaine, 0, 3, 'UTF-8');
        $majuscules = mb_strtoupper($troisPremiers, 'UTF-8');
        $sansAccents = self::removeAccents($majuscules);

        return $sansAccents;
    }
    
    public static function generateRandomAlphaCode($length = 10)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $alphaCode = '';

        for ($i = 0; $i < $length; $i++) {
            $alphaCode .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $alphaCode;
    }

    public static function removeAccents($str)
    {
        $str = str_replace(
           array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'à', 'á', 'â', 'ã', 'ä', 'å', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'È', 'É', 'Ê', 'Ë', 'è', 'é', 'ê', 'ë', 'Ç', 'ç', 'Ì', 'Í', 'Î', 'Ï', 'ì', 'í', 'î', 'ï', 'Ù', 'Ú', 'Û', 'Ü', 'ù', 'ú', 'û', 'ü', 'ÿ', 'Ñ', 'ñ'),
            array('A', 'A', 'A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a', 'a', 'O', 'O', 'O', 'O', 'O', 'O', 'o', 'o', 'o', 'o', 'o', 'o', 'E', 'E', 'E', 'E', 'e', 'e', 'e', 'e', 'C', 'c', 'I', 'I', 'I', 'I', 'i', 'i', 'i', 'i', 'U', 'U', 'U', 'U', 'u', 'u', 'u', 'u', 'y', 'N', 'n'),
            $str
        );

        return $str;
    }
    //End code 3 char
    
     public function categories()
    {
        return $this->belongsToMany(Categorie::class);
    }
    
      public function fournisseurs()
    {
        return $this->belongsTo(User::class);
    }
    
    
}
