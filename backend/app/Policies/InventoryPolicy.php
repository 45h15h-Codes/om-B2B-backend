<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryPolicy
{
    use HandlesAuthorization;

    /**
     * Resolve the active role of the user, supporting session-toggled roles.
     */
    protected function getActiveRole(User $user): string
    {
        return session('admin_role', $user->role);
    }

    /**
     * Determine if the user can view the product.
     */
    public function view(User $user, $product): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true;
        }

        // Normal Admin: can view if owned OR assigned to one of their stores
        return (int)$product->assigned_admin_id === (int)$user->id || 
               (int)$product->user_id === (int)$user->id ||
               $this->isProductAssignedToUserStore($user, $product);
    }

    /**
     * Determine if the user can place a hold on the product.
     */
    public function hold(User $user, $product): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true;
        }

        // Normal Admin: can hold if owned OR assigned to one of their stores
        return (int)$product->assigned_admin_id === (int)$user->id || 
               (int)$product->user_id === (int)$user->id ||
               $this->isProductAssignedToUserStore($user, $product);
    }

    /**
     * Determine if the user can release a hold on the product.
     */
    public function release(User $user, $product): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true; // Super Admin can override holds
        }

        // Normal Admin: can release if owned/assigned to them AND held by them
        $isOwnedOrAssigned = (int)$product->assigned_admin_id === (int)$user->id || 
                             (int)$product->user_id === (int)$user->id ||
                             $this->isProductAssignedToUserStore($user, $product);

        return $isOwnedOrAssigned && (int)$product->hold_by === (int)$user->id;
    }

    /**
     * Determine if the user can trigger a Shopify sync.
     */
    public function sync(User $user, $product): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true;
        }

        // Normal Admin: can sync if owned OR assigned to one of their stores
        return (int)$product->assigned_admin_id === (int)$user->id || 
               (int)$product->user_id === (int)$user->id ||
               $this->isProductAssignedToUserStore($user, $product);
    }

    /**
     * Determine if the user can edit/update the product details.
     */
    public function edit(User $user, $product): bool
    {
        $role = $this->getActiveRole($user);
        if ($role === 'super_admin') {
            return true;
        }

        // Only Diamond Owner (creator) can edit diamond data
        return (int)$product->assigned_admin_id === (int)$user->id || 
               (int)$product->user_id === (int)$user->id;
    }

    /**
     * Check if a diamond is assigned to any of the user's shops.
     */
    protected function isProductAssignedToUserStore(User $user, $product): bool
    {
        if ($product instanceof \App\Models\Diamond) {
            $storeIds = \App\Models\ShopifyStore::where('user_id', $user->id)->pluck('id')->toArray();
            return \App\Models\DiamondStoreAssignment::where('diamond_id', $product->id)
                ->whereIn('shopify_store_id', $storeIds)
                ->exists();
        }
        return false;
    }

    /**
     * Determine if the user can create requests.
     */
    public function request(User $user): bool
    {
        // Normal Admin can request
        return $this->getActiveRole($user) === 'normal_admin';
    }

    /**
     * Determine if the user can manage requests (approve/reject/complete).
     */
    public function manageRequests(User $user): bool
    {
        // Only Super Admin can approve/reject/complete
        return $this->getActiveRole($user) === 'super_admin';
    }
}
