<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcPos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Supprimer la procédure si elle existe déjà
        DB::unprepared('DROP PROCEDURE IF EXISTS GetPos');
        
       // Créer la nouvelle procédure
        DB::unprepared('
            CREATE PROCEDURE GetPos(IN pos_id INT)
            BEGIN
                SELECT 
                    ps.id,
                    ps.transaction_id,
                    ps.client_id,
                    CONCAT(us.prenoms, " ", us.nom) AS client,
                    ps.paid_method_id,
                    pd.libelle AS paid_method,
                    ps.tva,
                    ps.remise,
                    ps.qte_total,
                    CONCAT(cs.prenoms, " ", cs.nom) AS caissier,
                    ps.created_at,
                    ps.espece,
                    ps.monnaie
                FROM 
                    pos ps
                LEFT JOIN 
                    users us ON us.id = ps.client_id
                LEFT JOIN 
                    users cs ON cs.id = ps.created_user
                LEFT JOIN 
                    categories pd ON pd.id = ps.paid_method_id
                WHERE 
                    ps.id = pos_id;
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
        DB::unprepared('DROP PROCEDURE IF EXISTS GetPos');
    }
}
