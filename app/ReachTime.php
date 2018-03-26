<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReachTime extends Model
{
    protected $table = 'reach_times';

    protected $fillable = ['name','slug'];
}
