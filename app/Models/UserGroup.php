<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    //

    public function permission()
    {
    	return $this->belongsTo('App\Models\Permission', 'permissionId');
    }
}
