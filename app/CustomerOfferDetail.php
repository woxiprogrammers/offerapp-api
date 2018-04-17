<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerOfferDetail extends Model
{
    protected $table = 'customer_offer_details';

    protected $fillable = ['customer_id','offer_id','offer_status_id','is_wishlist','reach_time_id','offer_code','reward_points'];

    public function customer(){
        return $this->belongsTo('App\Customer','customer_id');
    }

    public function offer(){
        return $this->belongsTo('App\Offer','offer_id');
    }

    public function offerStatus(){
        return $this->belongsTo('App\OfferStatus','offer_status_id');
    }

    public function reachTime(){
        return $this->belongsTo('App\ReachTime','reach_time_id');
    }
}
