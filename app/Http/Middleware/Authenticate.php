<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->guest()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            } else if (
                $request->is(config('app.backend'))
                || $request->is(config('app.backend') . '/*')
            ) {
                return redirect()->guest(config('app.backend').'/login');
            } else {
                return redirect()->guest('login');
            }
        }

        return $next($request);
    }
}
