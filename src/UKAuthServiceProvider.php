<?php

namespace VATSIMUK\Support\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;
use VATSIMUK\Support\Auth\Auth\UKAuthGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use VATSIMUK\Support\Auth\Auth\UKAuthUserProvider;
use VATSIMUK\Support\Auth\Services\PermissionValidityService;

class UKAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/ukauth.php',
            'ukauth'
        );

        // add custom guard provider
        Auth::provider('ukauth', function ($app, array $config) {
            return new UKAuthUserProvider($app->make(config('ukauth.auth_user_model')));
        });
        Auth::extend('jwt', function ($app, $name, array $config) {
            return new UKAuthGuard(Auth::createUserProvider($config['provider']), $app->make('request'));
        });


        $this->registerRelationshipMacros();

        $this->registerPermissionsLogic();

        $this->registerFacades();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        /*
         * Note: It is preferred that the config is not published. If changed are required to the default,
         * publish the config and then delete any value you are not overriding.
         */
        $this->publishes([
            __DIR__ . '/../config/ukauth.php' => config_path('ukauth.php'),
        ], 'vatsimuk-auth-package');
    }

    private function registerRelationshipMacros()
    {
        $containsFunction = function ($related) {
            return $this->wherePivot($this->relatedPivotKey, is_object($related) ? $related->getKey() : $related)->exists();
        };

        BelongsToMany::macro('contains', $containsFunction);
        HasMany::macro('contains', $containsFunction);
    }

    private function registerPermissionsLogic()
    {
        Gate::before(function (Model $user, string $permission) {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo($permission) ?: null;
            }
        });
    }

    private function registerFacades()
    {
        $this->app->singleton('permissionvalidity', function ($app) {
            return app()->make(PermissionValidityService::class);
        });
    }
}
