<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
  protected $fillable = ['name', 'account', 'contactName', 'contactEmail', 'contactPhone'];

	public function stat()
  {
    return $this->hasOne('App\Models\Stat', 'organizationId');
  }

	public function faces()
  {
    return $this->hasManyThrough('App\Models\Face', 'App\Models\Faceset', 'organizationId' ,'facesetId');
  }  
  
  public function users()
  {
  	return $this->hasMany('App\Models\User', 'organizationId');
  }
}
