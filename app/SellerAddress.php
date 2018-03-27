<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SellerAddress extends Model
{
    protected $table = 'seller_addresses';

    protected $fillable = ['seller_id','shop_name','landline','address','floor_id','zipcode',
        'city','state','longitude','latitude','is_active'];

    public function seller(){
        return $this->belongsTo('App\Seller','seller_id');
    }

    public function floor(){
        return $this->belongsTo('App\Floor','floor_id');
    }

    public function sellerAddressImage(){
        return $this->hasMany('App\SellerAddressImage','seller_address_id');
    }

    public function offer(){
        return $this->hasMany('App\Offer','seller_address_id');
    }
}
