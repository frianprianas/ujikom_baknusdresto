<?php

namespace App\Providers;

use App\Models\User;
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
        View::composer('*', function ($view) {
            $activeUserId = session('active_user_id');
            $currentUser = $activeUserId ? User::find($activeUserId) : User::first();
            if (!$currentUser) {
                $currentUser = new User(['name' => 'Alex Morgan', 'role' => 'admin']);
            }
            $view->with('currentUser', $currentUser);
        });
    }
}
