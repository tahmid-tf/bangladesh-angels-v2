<?php

namespace App\Providers;

use App\Models\Investment;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Queue throttle for outbound campaign jobs (adjust per provider limits).
        RateLimiter::for('campaign-mails', function (): Limit {
            return Limit::perMinute(50)->by('global-campaign-mails');
        });

        // Share notification metrics with the admin layout
        View::composer('layouts.admin', function ($view) {
            if (auth()->check() && auth()->user()->isAdmin()) {
                $unreadCount = Investment::whereNull('viewed_at')->count();
                $recent = Investment::with(['user', 'deal', 'viewer'])
                    ->whereHas('deal')
                    ->latest('created_at')
                    ->take(8)
                    ->get();

                $view->with('unreadNotificationsCount', $unreadCount)
                     ->with('recentNotifications', $recent);
            } else {
                $view->with('unreadNotificationsCount', 0)
                     ->with('recentNotifications', collect());
            }
        });
    }
}
