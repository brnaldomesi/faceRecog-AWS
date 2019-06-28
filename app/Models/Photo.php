<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
  protected $fillable = ['arresteeId', 'filename', 'poseType', 'savedPath', 'photoDate'];

}
