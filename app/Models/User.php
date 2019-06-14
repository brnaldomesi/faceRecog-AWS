<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'organizationId', 'userGroupId', 'lastLogin', 'loginCount'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function cases()
    {
      return $this->hasMany('App\Models\Cases', 'userId');
    }

    public function userGroup()
    {
        return $this->belongsTo('App\Models\UserGroup', 'userGroupId');
    }

    public function getPermissionAttribute()
    {
        return $this->userGroup->permission;
    }

    public function compare()
    {
        return $this->hasMany('App\Models\Compare', 'user_id');
    }
}
