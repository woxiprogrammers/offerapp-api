<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SellerAddressImage extends Model
{
    protected $table = 'seller_address_images';

    protected $fillable = ['name','seller_address_id'];

    public function sellerAddress(){
        return $this->belongsTo('App\SellerAddress','seller_address_id');
    }
}
