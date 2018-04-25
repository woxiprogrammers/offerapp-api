<?php
/**
 * Created by PhpStorm.
 * User: sonali
 * Date: 28/3/18
 * Time: 11:07 AM
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{

    protected $table = 'otp_verification';

    protected $fillable = ['id', 'mobile_no', 'otp'];

    protected $hidden = ['mobile_no', 'otp'];
}