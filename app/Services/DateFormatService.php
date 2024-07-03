<?php

namespace App\Services;

/**
 * Class DateFormatService.
 */
class DateFormatService
{
    public function formatToDDMMYYYY($date)
    {
        if ($date !== null) {
            return date('d/m/Y', strtotime($date));
        } else {
            return null; // or any default value as needed
        }
    }
}
