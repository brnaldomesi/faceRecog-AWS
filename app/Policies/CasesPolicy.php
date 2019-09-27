<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Cases;

use Illuminate\Auth\Access\HandlesAuthorization;

use Log;

class CasesPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */

    public function __construct(User $user)
    {
    }

    public function before(User $user, $ability)
    {
        return null;
    }

    public function create(User $user)
    {
        return $user->permission->can_create_case;
    }

    public function update(User $user, Cases $case)
    {
        if (!$user->permission->can_edit_case) {
            return false;
        }
		
		if ($user->permission->isAdmin()) {
			// Lets Admin view all cases
			return true;
		} else {
			return $user->id === $case->userId;
		}
    }

    public function delete(User $user, Cases $case)
    {
        if (!$user->permission->can_edit_case) {
            return false;
        }
        return ($user->id === $case->userId); // || ($user->userGroupId == 2 && $user->organizationId == $case->organizationId);
    }

    public function view(User $user, Cases $case)
    {
        if ($user->id == $case->userId) {
            return true;
        } else {
            return $user->permission->can_view_case;
        }
    }
}
