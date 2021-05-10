<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;


class CreateVisitor
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
        // Set this in session so that AppController will not throw unauthorized error
        Session::put('noValidation', 1);

        // Create controller
        $controller = app()->make('App\Http\Controllers\VisitorController');
        $controller->callAction('createVisitor', [$request]); // Call controller method

        return $next($request);
    }
}
