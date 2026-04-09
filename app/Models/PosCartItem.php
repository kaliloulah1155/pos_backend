<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosCartItem extends Model
{
    use HasFactory;
     protected $table='pos_cart_items';

    protected $guarded=[];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'item_id');
    }

    public function pos()
    {
        return $this->belongsTo(Pos::class, 'pos_id');
    }
}
