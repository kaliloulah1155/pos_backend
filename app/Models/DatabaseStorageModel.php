<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseStorageModel extends Model
{
//
/**
 * Override eloquent default table
 * @var string
 */
protected $table = 'wishlist_storage';

/**
 * The attributes that are mass assignable.
 *
 * @var array
 */
protected $fillable = [
    'id', 'wishlist_data',
];


/**
 * Mutator for wishlist_column
 * @param $value
 */
public function setWishlistDataAttribute($value)
{
    $this->attributes['wishlist_data'] = serialize($value);
}


/**
 * Accessor for wishlist_column
 * @param $value
 * @return mixed
 */
public function getWishlistDataAttribute($value)
{
    return unserialize($value);
}

}