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
      'userId', 'event', 'ip'
  ];

  public function user()
  {
      return $this->belongsTo('App\Models\User', 'userId');
  }
}
