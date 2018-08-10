<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLog extends Model
{
     //
	/**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'userId', 'date_time', 'event'
  ];
}
