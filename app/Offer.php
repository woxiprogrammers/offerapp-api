<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $table = 'offers';

    protected $fillable = ['category_id','offer_type_id','seller_address_id','offer_status_id',
        'offer_id','description','valid_from','valid_to'
    ];

    public function offerImages(){
        return $this->hasMany('App\OfferImage','offer_id');
    }

    public function category(){
        return $this->belongsTo('App\Category','category_id');
    }

    public function sellerAddress(){
        return $this->belongsTo('App\SellerAddress','seller_address_id');
    }

    public function offerStatus(){
        return $this->belongsTo('App\OfferStatus','offer_status_id');
    }

    public function parentOffer(){
        return $this->belongsTo('App\Offer','offer_id');
    }

    public function offerType(){
        return $this->belongsTo('App\OfferType','offer_type_id');
    }

}
