<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaceTmp extends Model
{
    //
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organizationId','image_url', 'identifiers', 'gender','filename','personId','name','dob','pose','imagedate'
    ];
}
