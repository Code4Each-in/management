<?php

namespace App\Http\Controllers;

use App\Models\Modules;
use App\Models\Pages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ModulesController extends Controller
{
    public function index()
    {
        $routes = Route::getRoutes()->getRoutes();
        $routeNames = [];

        $excludedRouteNames = [
            'ignition.healthCheck',
            'ignition.executeSolution',
            'ignition.shareReport',
            'ignition.scripts',
            'ignition.styles',
            'login',
            'login.user',
            'logout',
        ];

        foreach ($routes as $route) {
            $routeName = $route->getName();

            if ($routeName && $routeName !== '' && !in_array($routeName, $excludedRouteNames)) {
                $routeNames[] = $routeName;
            }
        }

        $pages = Pages::orderBy('id','desc')->get();
        $modulesData = Modules::with('page')->orderBy('id','desc')->get();
        return view('modules.index', compact('modulesData','pages','routeNames'));
    }

    public function store(Request $request)
    {
		 $validator = \Validator::make($request->all(), [
            'pageId' => 'required', 
            'moduleName' => 'required', 
            'routeName' => 'required', 
        ],[
            'pageId.required' => 'The page field is required.'
        ]);        

        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }

        $pageId = $request->get('pageId');
        $moduleName = $request->get('moduleName');
        $routeName = $request->get('routeName');
        
        $module = Modules::Create([
            'page_id' => $pageId,
            'module_name' => $moduleName,
            'route_name' => $routeName,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

		$request->session()->flash('message','Module added successfully.');
        return Response()->json(['status'=>200, 'module'=>$module]);
    }

    public function edit(Request $request)
    {   
        $module  = Modules::where(['id' => $request->id])->first();
        return Response()->json(['module' =>$module]);
    }

    public function update(Request $request)
    {
		$validator = \Validator::make($request->all(), [
            'edit_page_id' => 'required',   
            'edit_module_name' => 'required',   
            'edit_route_name' => 'required',   
        ]);
 
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
		
        Modules::where('id', $request->id)
        ->update([
            'module_name' => $request->edit_module_name,
            'route_name' => $request->edit_route_name,
            'page_id' => $request->edit_page_id,
        ]);
		$request->session()->flash('message','Modules updated successfully.');
        return Response()->json(['status'=>200]);
    }


    public function destroy(Request $request)
    {
         $modules = Modules::where('id',$request->id)->delete();
         $request->session()->flash('message','Modules deleted successfully.');
        return Response()->json($modules);
    }


}
