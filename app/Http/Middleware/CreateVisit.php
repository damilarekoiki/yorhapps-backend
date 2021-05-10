<?php

namespace App\Http\Middleware;

// use App\Http\Controllers\VisitController;
use Closure;
use Illuminate\Http\Request;

class CreateVisit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        // $controller = new VisitController();
        // $controller->createVisit($request); // Call controller method

        $controller = app()->make('App\Http\Controllers\VisitController');
        $controller->callAction('createVisit', [$request]); // Call controller method

        return $next($request);
    }
}
