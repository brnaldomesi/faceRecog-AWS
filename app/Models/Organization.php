<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    //
	public function stat()
  {
    return $this->hasOne('App\Models\Stat', 'organizationId');
  }

	public function faces()
  {
    return $this->hasManyThrough('App\Models\Face', 'App\Models\Faceset', 'organizationId' ,'facesetId');
  }  
  
}
