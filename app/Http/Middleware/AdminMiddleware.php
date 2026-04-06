<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->role->name !== 'Super Admin') {

            return redirect()->route('dashboard.index')
                ->with('error', 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}