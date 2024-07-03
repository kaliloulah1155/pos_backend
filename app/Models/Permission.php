<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 


class Permission extends Model   
{
    use HasFactory,SoftDeletes;
      protected $table='permissions';
      protected $guarded = [];

    // Définir la relation action
     public function action()
     {
         return $this->belongsTo(\App\Model\Action::class, 'action_id');
     }

     // Définir la relation menu
     public function menu()
     {
         return $this->belongsTo(\App\Model\Menu::class, 'menu_id');
     }
     
     // Définir la relation profil
     public function profil()
     {
         return $this->belongsTo(\App\Model\Profil::class, 'profil_id');
     }
}
