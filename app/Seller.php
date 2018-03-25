<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    protected $table = 'seller';

    protected $fillable = ['user_id'];

    public function user(){
        return $this->belongsTo('App\User','user_id');
    }

    public function sellerAddress(){
        return $this->hasMany('App\SellerAddress','seller_id');
    }

    public function group(){
        return $this->hasMany('App\Group','seller_id');
    }

}
