<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Faceset extends Model
{
    //

		/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'facesetToken', 'organizationId'
    ];
}
