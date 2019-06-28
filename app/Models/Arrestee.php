<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Arrestee extends Model
{
  protected $fillable = ['organizationId', 'personId', 'name', 'dob', 'gender'];

  public function faces()
  {
    return $this->hasMany('App\Models\Face', 'personId','organizationId');
  }

}
