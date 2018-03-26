<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SellerPaymentMode extends Model
{
    protected $table = 'seller_payment_modes';

    protected $fillable = ['seller_id','payment_mode_id'];

    public function seller(){
        return $this->belongsTo('App\Seller','seller_id');
    }

    public function paymentMode(){
        return $this->belongsTo('App\PaymentMode','payment_mode_id');
    }
}
