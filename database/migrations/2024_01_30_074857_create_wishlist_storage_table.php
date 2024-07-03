<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWishlistStorageTable extends Migration
{
/**
 * Run the migrations.
 *
 * @return void
 */
 public function up()
{
    Schema::create('wishlist_storage', function (Blueprint $table) {
        $table->string('id')->index();
        $table->longText('wishlist_data');
        $table->timestamps();
        $table->primary('id');
    });
}

/**
 * Reverse the migrations.
 *
 * @return void
 */
public function down()
{
    Schema::dropIfExists('wishlist_storage');
}
}