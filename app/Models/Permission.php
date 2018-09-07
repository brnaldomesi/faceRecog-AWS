<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public function isAdmin()
    {
        return $this->can_edit_all_users || $this->can_manage_organization_aggrements;
    }
}
