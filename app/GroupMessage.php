<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    protected $table = 'group_messages';

    protected $fillable = ['group_id','role_id','offer_id','reference_member_id'];

    public function group(){
        return $this->belongsTo('App\Group','group_id');
    }

    public function role(){
        return $this->belongsTo('App\Role','role_id');
    }

    public function customer(){
        return $this->belongsTo('App\Customer','reference_member_id');
    }

    public function seller(){
        return $this->belongsTo('App\Seller','reference_member_id');
    }

    public function offer(){
        return $this->belongsTo('App\Offer','offer_id');
    }
}
