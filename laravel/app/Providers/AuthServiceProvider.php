<?php

namespace App\Providers;

use App\Models\Connection;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Policies\ConnectionPolicy;
use App\Policies\InvitationPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkspacePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Connection::class => ConnectionPolicy::class,
        Invitation::class => InvitationPolicy::class,
        Organization::class => OrganizationPolicy::class,
        User::class => UserPolicy::class,
        Workspace::class => WorkspacePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
