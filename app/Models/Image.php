<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    //

    public function getFilePathAttribute()
    {
    	return 'public/case/images/' . $this->filename_uploaded;
    }

    public function getThumbnailPathAttribute()
    {
    	return 'public/case/thumbnails/' . $this->filename_uploaded;
    }

    public function getFileUrlAttribute()
    {
        return 'storage/case/images/' . $this->filename_uploaded;
    }

    public function getThumbnailUrlAttribute()
    {
        return 'storage/case/thumbnails/' . $this->filename_uploaded;
    }

    public function cases()
    {
    	return $this->belongsTo('App\Models\Cases', 'caseId');
    }
}
