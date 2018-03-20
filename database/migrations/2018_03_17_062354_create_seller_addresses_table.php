<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSellerAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('seller_id');
            $table->foreign('seller_id')->references('id')->on('sellers')->onUpdate('cascade')->onUpdate('cascade');
            $table->string('shop_name',255)->nullable();
            $table->string('landline',20)->nullable();
            $table->longText('address')->nullable();
            $table->unsignedInteger('floor_id');
            $table->foreign('floor_id')->references('id')->on('floors')->onUpdate('cascade')->onUpdate('cascade');
            $table->string('zipcode',255)->nullable();
            $table->string('city',255)->nullable();
            $table->string('state',255)->nullable();
            $table->decimal('longitude')->nullable();
            $table->decimal('latitude')->nullable();
            $table->boolean('is_active');
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
        Schema::dropIfExists('seller_addresses');
    }
}
