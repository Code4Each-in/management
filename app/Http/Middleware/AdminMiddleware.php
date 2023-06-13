<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Check if the authenticated user is an admin
        if ($request->user() && $request->user()->role->name == "Super Admin") {
            return $next($request);
        }

        // Redirect or show an error message if the user is not authorized
        // abort(403);
        return response()->view('unauthorized', [], 403);
    }
}