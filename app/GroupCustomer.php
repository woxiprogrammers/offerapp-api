<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GroupCustomer extends Model
{
    protected $table = 'group_customers';

    protected $fillable = [
        'group_id','customer_id'
    ];

    public function group(){
        return $this->belongsTo('App\Group','group_id');
    }

    public function customer(){
        return $this->belongsTo('App\Customer','customer_id');
    }
}
