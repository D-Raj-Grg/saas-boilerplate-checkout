<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceSetting;
use App\Observers\UserObserver;
use App\Observers\WorkspaceObserver;
use App\Observers\WorkspaceSettingObserver;
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
        // Register model observers
        Workspace::observe(WorkspaceObserver::class);
        WorkspaceSetting::observe(WorkspaceSettingObserver::class);
        User::observe(UserObserver::class);
    }
}
