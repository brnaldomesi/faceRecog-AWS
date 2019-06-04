<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
  protected $fillable = ['name', 'account', 'contactName', 'contactEmail', 'contactPhone','aws_collection_male_id','aws_collection_female_id','aws_collection_cases_id'];

	public function stat()
  {
    return $this->hasOne('App\Models\Stat', 'organizationId');
  }

	public function faces()
  {
    return $this->hasManyThrough('App\Models\Face', 'App\Models\Faceset', 'organizationId' ,'facesetId');
  }
  
  public function facesTmp()
  {
    return $this->hasMany('App\Models\FaceTmp', 'organizationId');
  }
  
  public function users()
  {
  	return $this->hasMany('App\Models\User', 'organizationId');
  }

  public function cases()
  {
    return $this->hasMany('App\Models\Cases', 'organizationId');
  }
}
