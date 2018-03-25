<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OfferImage extends Model
{
    protected $table = 'offer_images';

    protected $fillable = ['name','offer_id'];

    public function offer(){
        return $this->belongsTo('App\Offer','offer_id');
    }
}
