<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacesetSharing extends Model
{
    public function owner()
    {
        return $this->belongsTo('App\Models\Organization', 'organization_owner');
    }

    public function requestor()
    {
        return $this->belongsTo('App\Models\Organization', 'organization_requestor');
    }
}
