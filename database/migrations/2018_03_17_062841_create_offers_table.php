<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories')->onUpdate('cascade')->onUpdate('cascade');
            $table->unsignedInteger('offer_type_id');
            $table->foreign('offer_type_id')->references('id')->on('offer_types')->onUpdate('cascade')->onUpdate('cascade');
            $table->unsignedInteger('seller_address_id');
            $table->foreign('seller_address_id')->references('id')->on('seller_addresses')->onUpdate('cascade')->onUpdate('cascade');
            $table->unsignedInteger('offer_status_id');
            $table->foreign('offer_status_id')->references('id')->on('offer_statuses')->onUpdate('cascade')->onUpdate('cascade');
            $table->unsignedInteger('offer_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
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
        Schema::dropIfExists('offers');
    }
}
