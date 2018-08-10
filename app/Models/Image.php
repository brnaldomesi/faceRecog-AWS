<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    //

    public function getFilePathAttribute()
    {
    	return $this->filename_uploaded;
    }

    public function getThumbnailPathAttribute()
    {
    	return $this->filename_uploaded;
    }

    public function getFileUrlAttribute()
    {
        return $this->filename_uploaded;
    }

    public function getThumbnailUrlAttribute()
    {
        return $this->filename_uploaded;
    }

    public function cases()
    {
    	return $this->belongsTo('App\Models\Cases', 'caseId');
    }
}
