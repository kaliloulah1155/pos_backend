<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Salaire extends Model
{
    use HasFactory,SoftDeletes;
      protected $guarded = [];
      
    public function users()
    {
        return $this->belongsTo(User::class);
    }
}
