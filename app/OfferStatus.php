<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OfferStatus extends Model
{
    protected $table = 'offer_statuses';

    protected $fillable = ['name','slug','type'];
}
