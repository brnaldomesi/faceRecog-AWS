<?php

namespace App\Models;

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
        'facesetToken', 'organizationId', 'gender'
    ];

    public function organization()
    {
        return $this->belongsTo('App\Models\Organization', 'organizationId');
    }
}
