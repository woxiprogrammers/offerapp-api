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
}
