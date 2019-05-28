<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cases extends Model
{
    //

    public function images()
    {
    	return $this->hasMany('App\Models\Image', 'caseId');
    }

    public function caseSearches()
    {
    	return $this->hasManyThrough('App\Models\CaseSearch', 'App\Models\Image', 'caseId', 'imageId');
    }

    public function organization()
    {
        return $this->belongsTo('App\Models\Organization', 'organizationId');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'userId');
    }
}
