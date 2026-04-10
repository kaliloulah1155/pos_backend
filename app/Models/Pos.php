<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pos extends Model
{
    use HasFactory;

    protected $table='pos';

    protected $guarded=[];

    public function items()
    {
        return $this->hasMany(PosCartItem::class, 'pos_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
