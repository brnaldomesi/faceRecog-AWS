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
        'faceToken','savedPath','facesetId', 'imageId','filename','personId','organizationId','identifiers', 'gender', 'faceMatches', 'aws_face_id'
    ];
    
    public function faceset()
    {
        return $this->belongsTo('App\Models\Faceset', 'facesetId');
    }
}
