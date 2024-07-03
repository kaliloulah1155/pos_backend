<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableMedia extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media', function (Blueprint $table) {
             $table->string('table')->nullable();
             $table->text('lien')->nullable();
             $table->string('format')->nullable();
            $table->string('libelle')->nullable();
            $table->integer('table_id')->nullable();
             $table->integer('status')->nullable();
             $table->string('slug')->nullable();
             $table->integer('created_user')->nullable();
            $table->integer('updated_user')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('media', function (Blueprint $table) {
            //
        });
    }
}
