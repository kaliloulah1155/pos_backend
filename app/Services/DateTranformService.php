<?php

namespace App\Services;
use Illuminate\Support\Carbon;

/**
 * Class DateTranformService.
 */
class DateTranformService
{
    
    public function transformToYYYYMMDD($userProvidedDate, $userDateFormat = 'd/m/Y')
    {
        if ($userProvidedDate !== null) {
            // Parse the input date using Carbon and then format it
            return Carbon::createFromFormat($userDateFormat, $userProvidedDate)->format('Y-m-d');
        } else {
            return null; // or any default value as needed
        }
    }

}
