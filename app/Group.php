<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'groups';

    protected $fillable = ['name','description','seller_id'];

    public function seller(){
        return $this->belongsTo('App\Seller','seller_id');
    }
}