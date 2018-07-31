<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Image;
use App\Models\Cases;

use Illuminate\Auth\Access\HandlesAuthorization;

class ImagePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function delete(User $user, Image $image)
    {
        if ($image->cases->status != 'ACTIVE') {
            return false;
        }
        return $user->id === $image->cases->userId;
    }
}
