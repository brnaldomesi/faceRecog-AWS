<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compare extends Model
{
    //
    protected $fillable = ['user_id', 'imageUrl1', 'imageUrl2', 'similarity'];
}
