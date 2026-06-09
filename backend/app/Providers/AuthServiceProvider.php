<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Diamond::class => \App\Policies\DiamondPolicy::class,
        \App\Models\Jewelery::class => \App\Policies\JeweleryPolicy::class,
        \App\Models\InventoryRequest::class => \App\Policies\InventoryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        \Illuminate\Support\Facades\Gate::define('view-health', function ($user) {
            return session('admin_role', $user->role) === 'super_admin';
        });

        \Illuminate\Support\Facades\Gate::define('run-recovery', function ($user) {
            return session('admin_role', $user->role) === 'super_admin';
        });

        \Illuminate\Support\Facades\Gate::define('view-logs', function ($user) {
            return session('admin_role', $user->role) === 'super_admin';
        });
    }
}
