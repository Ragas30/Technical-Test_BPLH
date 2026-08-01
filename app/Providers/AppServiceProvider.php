<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\Review;
use App\Models\User;
use App\Policies\DocumentPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(ProjectDocument::class, DocumentPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);

        Gate::define('access-dashboard', fn (User $user): bool => $user->hasPermissionTo(Permission::DashboardView));

        Gate::before(fn (User $user, string $ability): ?bool => $user->hasRole(Role::Admin) ? true : null);
    }
}
