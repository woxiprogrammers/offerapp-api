<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $table = 'offers';

    protected $fillable = ['category_id'];

    public function offerImages(){
        return $this->hasMany('App\OfferImage','offer_id');
    }
}
