<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Jewelery;
use Illuminate\Auth\Access\HandlesAuthorization;

class JeweleryPolicy
{
    use HandlesAuthorization;

    protected function getActiveRole(User $user): string
    {
        return session('admin_role', $user->role);
    }

    public function view(User $user, Jewelery $jewelry): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true;
        }

        // Normal Admin: can view if owned or assigned
        return (int)$jewelry->assigned_admin_id === (int)$user->id || 
               (int)$jewelry->user_id === (int)$user->id;
    }

    public function create(User $user): bool
    {
        return true; // Any admin can create jewelry items
    }

    public function update(User $user, Jewelery $jewelry): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true;
        }

        // Normal Admin: can edit if assigned or owned
        return (int)$jewelry->assigned_admin_id === (int)$user->id || 
               (int)$jewelry->user_id === (int)$user->id;
    }

    public function delete(User $user, Jewelery $jewelry): bool
    {
        // Only Super Admin can delete items
        return $this->getActiveRole($user) === 'super_admin';
    }

    public function publish(User $user, Jewelery $jewelry): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true;
        }

        // Normal Admin: can sync/publish if assigned or owned
        return (int)$jewelry->assigned_admin_id === (int)$user->id || 
               (int)$jewelry->user_id === (int)$user->id;
    }

    public function hold(User $user, Jewelery $jewelry): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true;
        }

        // Normal Admin: can hold if owned or assigned
        return (int)$jewelry->assigned_admin_id === (int)$user->id || 
               (int)$jewelry->user_id === (int)$user->id;
    }

    public function release(User $user, Jewelery $jewelry): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true;
        }

        // Normal Admin: can release if owned/assigned AND held by them
        $isOwnedOrAssigned = (int)$jewelry->assigned_admin_id === (int)$user->id || 
                              (int)$jewelry->user_id === (int)$user->id;

        return $isOwnedOrAssigned && (int)$jewelry->hold_by === (int)$user->id;
    }

    public function sync(User $user, Jewelery $jewelry): bool
    {
        return $this->publish($user, $jewelry);
    }

    public function edit(User $user, Jewelery $jewelry): bool
    {
        return $this->update($user, $jewelry);
    }
}
