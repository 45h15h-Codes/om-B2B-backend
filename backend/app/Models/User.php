<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'shopify_store',
        'shopify_access_token',
        'active_shopify_store_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function diamonds()
    {
        return $this->hasMany(Diamond::class);
    }

    public function jeweleries()
    {
        return $this->hasMany(Jewelery::class);
    }

    public function shopifyStores()
    {
        return $this->hasMany(ShopifyStore::class);
    }

    public function activeShopifyStore()
    {
        return $this->belongsTo(ShopifyStore::class, 'active_shopify_store_id');
    }

    public function ordersCreated()
    {
        return $this->hasMany(Order::class, 'created_by');
    }

    public function ordersApproved()
    {
        return $this->hasMany(Order::class, 'approved_by');
    }

    public function inventoryRequests()
    {
        return $this->hasMany(InventoryRequest::class);
    }

    /**
     * Get the user's notifications.
     */
    public function notifications()
    {
        return $this->morphMany(\App\Models\Notification::class, 'notifiable')->latest();
    }

    /**
     * Get the user's unread notifications.
     */
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * Get permissions associated with this user.
     */
    public function permissions()
    {
        return $this->hasMany(AdminPermission::class);
    }

    /**
     * Internal helper to collect all unique config permissions.
     */
    protected function allPermissions(): array
    {
        return collect(config('admin_permissions'))
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Clear and immediately rebuild the cached permissions list.
     */
    public function refreshPermissionsCache()
    {
        \Illuminate\Support\Facades\Cache::forget("user_permissions_{$this->id}");

        if ($this->role === 'super_admin') {
            return $this->allPermissions();
        }

        return \Illuminate\Support\Facades\Cache::rememberForever(
            "user_permissions_{$this->id}",
            function () {
                return $this->permissions()->pluck('permission')->toArray();
            }
        );
    }

    /**
     * Get user permissions from the cache (or query and save if not loaded).
     */
    public function getCachedPermissions(): array
    {
        if ($this->role === 'super_admin') {
            return $this->allPermissions();
        }

        return \Illuminate\Support\Facades\Cache::rememberForever(
            "user_permissions_{$this->id}",
            function () {
                return $this->permissions()->pluck('permission')->toArray();
            }
        );
    }

    /**
     * Check if user is granted a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getCachedPermissions());
    }

    /**
     * Check if user is granted at least one of the specified permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $granted = $this->getCachedPermissions();
        foreach ($permissions as $permission) {
            if (in_array($permission, $granted)) {
                return true;
            }
        }
        return false;
    }
}


