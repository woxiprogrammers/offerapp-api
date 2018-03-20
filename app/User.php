<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = [
        'id','role_id','first_name','last_name','password','email','mobile_no','profile_picture'];


}
