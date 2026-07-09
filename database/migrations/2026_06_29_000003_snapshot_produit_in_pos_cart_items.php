<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Une ligne de vente doit être un INSTANTANÉ, pas une référence vivante.
 *
 * Avant : pos_cart_items.item_id -> produits.id ON DELETE CASCADE
 *         => supprimer un produit détruisait les lignes des ventes passées.
 *
 * Après : le libellé (et l'image) sont figés à la vente, et la suppression
 *         d'un produit met simplement item_id à NULL : l'historique est préservé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_cart_items', function (Blueprint $table) {
            $table->string('libelle')->nullable()->after('item_id');
            $table->string('image')->nullable()->after('libelle');
        });

        // Figer le libellé/image des lignes existantes encore rattachées à un produit
        DB::statement('
            UPDATE pos_cart_items ct
            JOIN produits p ON p.id = ct.item_id
            SET ct.libelle = p.libelle, ct.image = p.image
            WHERE ct.libelle IS NULL
        ');

        // Supprimer un produit ne doit plus effacer l'historique de ventes
        Schema::table('pos_cart_items', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->foreign('item_id')->references('id')->on('produits')->nullOnDelete();
        });

        // Le reçu utilise le libellé figé, avec repli sur le produit s'il existe encore
        DB::unprepared('DROP PROCEDURE IF EXISTS GetPosItem');
        DB::unprepared('
            CREATE PROCEDURE GetPosItem(IN pos_id INT)
            BEGIN
                SELECT
                ct.id,
                ct.pos_id,
                ct.item_id,
                COALESCE(ct.libelle, pod.libelle) produit,
                ct.qte,
                ct.price,
                ct.price_by_qte
                FROM pos_cart_items ct
                LEFT JOIN produits pod ON pod.id = ct.item_id
                WHERE ct.pos_id = pos_id;
            END
        ');
    }

    public function down(): void
    {
        Schema::table('pos_cart_items', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->foreign('item_id')->references('id')->on('produits')->onDelete('cascade');
            $table->dropColumn(['libelle', 'image']);
        });

        DB::unprepared('DROP PROCEDURE IF EXISTS GetPosItem');
        DB::unprepared('
            CREATE PROCEDURE GetPosItem(IN pos_id INT)
            BEGIN
                SELECT ct.id, ct.pos_id, ct.item_id, pod.libelle produit,
                       ct.qte, ct.price, ct.price_by_qte
                FROM pos_cart_items ct
                LEFT JOIN produits pod ON pod.id = ct.item_id
                WHERE ct.pos_id = pos_id;
            END
        ');
    }
};
