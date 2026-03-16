<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// class ApiLogger
// {
//     public function handle($request, Closure $next)
//     {
//         $response = $next($request);

//         try {

//             if ($request->is('api/*')) {

//                 project_log(
//                     null,
//                     'info',
//                     'API Request',
//                     [
//                         'endpoint' => $request->path(),
//                         'method' => $request->method(),
//                         'status' => $response->status(),
//                     ],
//                     'api'
//                 );

//             }

//         } catch (\Throwable $e) {
//             // prevent crash
//         }

//         return $response;
//     }
// }
