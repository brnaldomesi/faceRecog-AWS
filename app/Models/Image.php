<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    //

    public function getFilePathAttribute()
    {
    	return env('AWS_S3_REAL_OBJECT_URL_DOMAIN').'storage/case/images/' . $this->filename_uploaded;
    }

    public function getThumbnailPathAttribute()
    {
    	return env('AWS_S3_REAL_OBJECT_URL_DOMAIN').'storage/case/thumbnails/' . $this->filename_uploaded;
    }

    public function getFileUrlAttribute()
    {
        return env('AWS_S3_REAL_OBJECT_URL_DOMAIN').'storage/case/images/' . $this->filename_uploaded;
    }

    public function getThumbnailUrlAttribute()
    {
        return env('AWS_S3_REAL_OBJECT_URL_DOMAIN').'storage/case/thumbnails/' . $this->filename_uploaded;
    }

    public function cases()
    {
    	return $this->belongsTo('App\Models\Cases', 'caseId');
    }
}
