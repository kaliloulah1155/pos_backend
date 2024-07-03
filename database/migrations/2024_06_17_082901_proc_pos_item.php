<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProcPosItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       // Supprimer la procédure si elle existe déjà
        DB::unprepared('DROP PROCEDURE IF EXISTS GetPosItem');
        
         // Créer la nouvelle procédure
        DB::unprepared('
            CREATE PROCEDURE GetPosItem(IN pos_id INT)
            BEGIN
                SELECT 
                ct.id,
                ct.pos_id,
                ct.item_id,
                pod.libelle produit,
                ct.qte,
                ct.price,
                ct.price_by_qte
                FROM pos_cart_items ct
                LEFT JOIN produits pod ON pod.id=ct.item_id
                WHERE ct.pos_id=pos_id;
            END
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Supprimer la procédure si elle existe déjà
        DB::unprepared('DROP PROCEDURE IF EXISTS GetPosItem');
    }
}
