<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerOfferDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_offer_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onUpdate('cascade')->onUpdate('cascade');
            $table->unsignedInteger('offer_id');
            $table->foreign('offer_id')->references('id')->on('offers')->onUpdate('cascade')->onUpdate('cascade');
            $table->unsignedInteger('offer_status_id');
            $table->foreign('offer_status_id')->references('id')->on('offer_statuses')->onUpdate('cascade')->onUpdate('cascade');
            $table->boolean('is_wishlist')->nullable();
            $table->unsignedInteger('reach_time_id')->nullable();
            $table->foreign('reach_time_id')->references('id')->on('reach_times')->onUpdate('cascade')->onUpdate('cascade');
            $table->string('offer_code',255)->nullable();
            $table->double('reward_points')->nullable();
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
        Schema::dropIfExists('customer_offer_details');
    }
}
