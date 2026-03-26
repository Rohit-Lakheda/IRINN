<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplicationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // TODO: Implement your Application authentication logic here
        // Example:
        // if (!auth()->check()) {
        //     return redirect()->route('register.index');
        // }
        
        return $next($request);
    }
}

