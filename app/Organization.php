<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    //
	public function stat()
  {
    return $this->hasOne('App\Stat', 'organizationId');
  }

	public function faces()
  {
    return $this->hasManyThrough('App\Face', 'App\Faceset', 'organizationId' ,'facesetId');
  }  
  
}
