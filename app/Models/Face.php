<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Face extends Model
{
    //
	/**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'faceToken', 'facesetId', 'imageId', 'identifiers', 'gender', 'faceMatches', 'savedPath'
  ];
}
