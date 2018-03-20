<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSellerAddressImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_address_images', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('seller_address_id');
            $table->foreign('seller_address_id')->references('id')->on('seller_addresses')->onUpdate('cascade')->onUpdate('cascade');
            $table->string('name',255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_address_images');
    }
}
