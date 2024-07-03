namespace App\Services;



class AmountServices

{

     public static function formatAmount($amount)

    {

         if ($amount !== null) {

             return number_format($amount, 0, ',', ' ');

         } else {

             return '0'; // or return number_format(0, 0, ',', ' '); if you want to format 0 as well

         }

    }

}

