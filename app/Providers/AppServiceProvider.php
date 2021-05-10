<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Arr;

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
        //

        // For using the correct gaurd
        // $this->app['router']->matched(function (\Illuminate\Routing\Events\RouteMatched $e) {
        //     $route = $e->route;
        //     if (!Arr::has($route->getAction(), 'guard')) {
        //         return;
        //     }
        //     $routeGuard = Arr::get($route->getAction(), 'guard');
        //     $this->app['auth']->resolveUsersUsing(function ($guard = null) use ($routeGuard) {
        //         return $this->app['auth']->guard($routeGuard)->user();
        //     });
        //     $this->app['auth']->setDefaultDriver($routeGuard);
        // });
    }
}
