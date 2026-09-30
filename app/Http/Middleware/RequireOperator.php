<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireOperator
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('operator_authenticated')) {
            return redirect('/login');
        }

        return $next($request);
    }
}
