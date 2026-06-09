<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
            'diamond' => \App\Models\Diamond::class,
            'jewelry' => \App\Models\Jewelery::class,
        ]);

        view()->composer('layouts.app', function ($view) {
            if (auth()->check()) {
                $user = auth()->user();
                $unreadCount = $user->unreadNotifications()->count();
                $recent = $user->notifications()->take(20)->get();
                $view->with([
                    'unreadNotificationsCount' => $unreadCount,
                    'recentNotifications' => $recent,
                ]);
            } else {
                $view->with([
                    'unreadNotificationsCount' => 0,
                    'recentNotifications' => collect(),
                ]);
            }
        });

        if (request()->headers->has('X-Forwarded-Host')) {
            $host = request()->header('X-Forwarded-Host');
            $proto = request()->header('X-Forwarded-Proto', 'https');
            config(['app.url' => $proto . '://' . $host]);
            app('url')->forceRootUrl($proto . '://' . $host);
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            $proto = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            config(['app.url' => $proto . '://' . $_SERVER['HTTP_HOST']]);
            app('url')->forceRootUrl($proto . '://' . $_SERVER['HTTP_HOST']);
        }
    }
}
