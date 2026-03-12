<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// class ApiLogger
// {
//     /**
//      * Handle an incoming request.
//      *
//      * @param  \Illuminate\Http\Request  $request
//      * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
//      * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
//      */
//     public function handle(Request $request, Closure $next)
//     {
//         $response = $next($request);
//         try {
//           if($request ->is('api/*')) {
//            project_log(
//             null,
//             'info',
//             'API Hit',
//             [
//                 'URL' => $request->fullUrl(),
//                 'method' => $request->method(),
//                 'status' => $response->getStatusCode(),
//             ],
//             'api'
//            );
//           }
//         } catch (\Exception $e) {
//             // pervent crashing if logging fails
//         }
//         return $response;
//     }
// }
class ApiLogger
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        try {

            if ($request->is('api/*')) {

                project_log(
                    null,
                    'info',
                    'API Request',
                    [
                        'endpoint' => $request->path(),
                        'method' => $request->method(),
                        'status' => $response->status(),
                    ],
                    'api'
                );

            }

        } catch (\Throwable $e) {
            // prevent crash
        }

        return $response;
    }
}
