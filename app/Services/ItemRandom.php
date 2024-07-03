<?php

namespace App\Services;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/**
 * Class ItemRandom.
 */
class ItemRandom
{
    
   /**
     * Generate a random code with the current date and time.
     *
     * @return string
     */
    public function generateRandomItemID()
    {
        $now = Carbon::now();
        $randomCode = 'item_' . $now->format('YmdHis') . '_' . Str::random(8);

        return $randomCode;
    }

}
