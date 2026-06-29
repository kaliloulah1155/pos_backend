<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les champs nécessaires aux commandes passées en ligne
     * (landing page / QR code) depuis la boutique.
     */
    public function up(): void
    {
        Schema::table('pos', function (Blueprint $table) {
            // 'pos'    => commande passée au point de vente (existant)
            // 'online' => commande passée en ligne par un client
            $table->string('order_type')->default('pos')->after('status');

            // Cycle de vie d'une commande en ligne :
            // pending | accepted | rejected | completed
            $table->string('order_status')->nullable()->after('order_type');

            // Coordonnées du client (commande en ligne, pas forcément un user)
            $table->string('customer_name')->nullable()->after('order_status');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->string('customer_address')->nullable()->after('customer_phone');
            $table->text('note')->nullable()->after('customer_address');
        });
    }

    public function down(): void
    {
        Schema::table('pos', function (Blueprint $table) {
            $table->dropColumn([
                'order_type',
                'order_status',
                'customer_name',
                'customer_phone',
                'customer_address',
                'note',
            ]);
        });
    }
};
