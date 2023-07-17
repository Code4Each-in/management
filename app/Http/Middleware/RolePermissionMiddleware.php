<?php

namespace App\Http\Middleware;

use App\Models\Modules;
use App\Models\RolePermission;
use Closure;
use Illuminate\Http\Request;
use App\Models\Users;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class RolePermissionMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();
        $role = $user->role; 
        if(auth()->user()->isAdmin())
        {
            return $next($request);
        }else{
            //Get The Name Of Route From Request 
           $name = \Request::route()->getName();
           //find Modules Related To The Route Name
           $modules = Modules::where('route_name',$name)->first();
           if ($modules == null) {
            return Redirect::back()->with('error', 'Permission Module Is Not Registered. Try to Access Again After Register.');
            
           }
           $moduleId = $modules->id;
           $role_id = $user->role_id;
           $role_permission = RolePermission::where('role_id',$role_id)->where('module_id',$moduleId)->exists();
           if($role_permission){
                return $next($request);
           }
        }
            
        // If the user doesn't have the required role or permission, you can return a response or redirect as needed
        return Redirect::back()->with('error', 'You do not have permission to access this page.');
    }
}