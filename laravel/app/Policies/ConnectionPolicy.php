<?php

namespace App\Policies;

use App\Models\Connection;
use App\Models\User;

class ConnectionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // User can view connections if they have a current workspace
        return $user->current_workspace_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Connection $connection): bool
    {
        return $user->canViewConnection($connection);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // User can create connections if they have a current workspace
        return $user->current_workspace_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Connection $connection): bool
    {
        return $user->canUpdateConnection($connection);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Connection $connection): bool
    {
        return $user->canDeleteConnection($connection);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Connection $connection): bool
    {
        return $this->delete($user, $connection);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Connection $connection): bool
    {
        return $this->delete($user, $connection);
    }
}
