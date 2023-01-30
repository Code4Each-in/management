<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Roles;
use App\Models\RolePermission;
use App\Models\Pages;
use App\Models\Modules;

class RolesController extends Controller
{
     /**
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
		$roleData=Roles::all(); //database query
		$pages=Pages::with('module')->get();
        return view('roles.index',compact('roleData','pages'));
		//compact for send variable to other file
    }
  /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
		 $validator = \Validator::make($request->all(), [
            'roleName' => 'required',       
        ]);
    
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
		
        $roleName = $request->get('roleName');
		$userPage=$request->get('userPage');
		$departmentPage=$request->get('departmentPage');
			
        $role =Roles::create([
            'name' => $roleName,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
		
		$rolePermission =RolePermission::create([
            'role_id' => $role->id,
            'users_page' => $userPage,
            'departments_page' => $departmentPage,
        ]);
		
		$request->session()->flash('message','Role added successfully.');
        return Response()->json(['status'=>200, 'role'=>$role]);
    }
	 public function edit(Request $request)
    {   
	
        $role  = Roles::where(['id' => $request->id])->first();
        return Response()->json(['role' =>$role]);
    }
	/**
     * Update.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
			 $validator = \Validator::make($request->all(), [
			 'id'=>'required',
            'name' => 'required', 
			
        ]);
		 if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
		
        Roles::where('id', $request->id)
        ->update([
            'name' => $request->name
        ]);
		$request->session()->flash('message','Role updated successfully.');
        return Response()->json(['status'=>200]);
    }
	 public function destroy(Request $request)
    {
        $Roles = Roles::where('id',$request->id)->delete();
     
	  $request->session()->flash('message','Role deleted successfully.');
     return Response()->json($Roles);
    }

}
