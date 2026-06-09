<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Diamond;
use App\Models\DiamondStoreAssignment;
use App\Models\ShopifyStore;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiamondPolicy
{
    use HandlesAuthorization;

    protected function getActiveRole(User $user): string
    {
        return session('admin_role', $user->role);
    }

    public function view(User $user, Diamond $diamond): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true;
        }

        // Normal Admin: can view if owned or assigned
        $storeIds = ShopifyStore::where('user_id', $user->id)->pluck('id')->toArray();
        $isAssigned = DiamondStoreAssignment::where('diamond_id', $diamond->id)
            ->whereIn('shopify_store_id', $storeIds)
            ->exists();

        return (int)$diamond->assigned_admin_id === (int)$user->id || 
               (int)$diamond->user_id === (int)$user->id ||
               $isAssigned;
    }

    public function create(User $user): bool
    {
        return true; // Any admin can create diamonds
    }

    public function update(User $user, Diamond $diamond): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true;
        }

        // Normal Admin: can edit if assigned or owned
        return (int)$diamond->assigned_admin_id === (int)$user->id || 
               (int)$diamond->user_id === (int)$user->id;
    }

    public function delete(User $user, Diamond $diamond): bool
    {
        // Only Super Admin can delete items
        return $this->getActiveRole($user) === 'super_admin';
    }

    public function publish(User $user, Diamond $diamond): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true;
        }

        // Normal Admin: can sync/publish if assigned or owned
        return (int)$diamond->assigned_admin_id === (int)$user->id || 
               (int)$diamond->user_id === (int)$user->id;
    }

    public function hold(User $user, Diamond $diamond): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true;
        }

        // Normal Admin: can hold if owned or assigned
        $storeIds = ShopifyStore::where('user_id', $user->id)->pluck('id')->toArray();
        $isAssigned = DiamondStoreAssignment::where('diamond_id', $diamond->id)
            ->whereIn('shopify_store_id', $storeIds)
            ->exists();

        return (int)$diamond->assigned_admin_id === (int)$user->id || 
               (int)$diamond->user_id === (int)$user->id ||
               $isAssigned;
    }

    public function release(User $user, Diamond $diamond): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true;
        }

        // Normal Admin: can release if owned/assigned AND held by them
        $storeIds = ShopifyStore::where('user_id', $user->id)->pluck('id')->toArray();
        $isAssigned = DiamondStoreAssignment::where('diamond_id', $diamond->id)
            ->whereIn('shopify_store_id', $storeIds)
            ->exists();

        $isOwnedOrAssigned = (int)$diamond->assigned_admin_id === (int)$user->id || 
                              (int)$diamond->user_id === (int)$user->id ||
                              $isAssigned;

        return $isOwnedOrAssigned && (int)$diamond->hold_by === (int)$user->id;
    }

    public function sync(User $user, Diamond $diamond): bool
    {
        return $this->publish($user, $diamond);
    }

    public function edit(User $user, Diamond $diamond): bool
    {
        return $this->update($user, $diamond);
    }
}
