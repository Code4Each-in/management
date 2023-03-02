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
		$roleData=Roles::orderBy('id','desc')->get(); //database query
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
            'role_name' => 'required',       
        ]);
    
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
		}        		
		$validate = $validator->valid();			
        $role =Roles::create([
            'name' => $validate['role_name'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
		if (isset($validate['role_permissions']))
		{
			foreach($validate['role_permissions'] as $permission)
			{				
				$rolePermission =RolePermission::create([
					'role_id' => $role->id,
					'module_id' => $permission,
				]);
			}
		}
		$request->session()->flash('message','Role added successfully.');
        return Response()->json(['status'=>200, 'role'=>$role]);
    }
	
	 public function edit(Request $request)
    {   
		$RolePermission=RolePermission::where(['role_id' => $request->id])->get();
        $role  = Roles::where(['id' => $request->id])->first();
        return Response()->json(['role' =>$role, 'RolePermission' =>$RolePermission]);
				$validate = $validator->valid();
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
			 'role_id'=>'required',
            'edit_role_name' => 'required', 			
        ]);
		 if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
		$validate = $validator->valid();

		$RolePermission=RolePermission::where(['role_id' => $validate['role_id']])->get(); 
		if (!empty($RolePermission))
		{
		      RolePermission::where('role_id', $validate['role_id'])->delete();			
		}	
		if (isset($validate['role_permissions']))
		{
			foreach($validate['role_permissions'] as $permission)
			{	
				$rolePermission =RolePermission::create([
					'role_id' => $validate['role_id'],
					'module_id' => $permission,
				]);
			}		
		}					
		Roles::where('id', $validate['role_id'])
        ->update([
            'name' => $validate['edit_role_name']
        ]);
		$request->session()->flash('message','Role updated successfully.');
        return Response()->json(['status'=>200]);
    }		
	 public function destroy(Request $request)
    {
        $Roles = Roles::where('id',$request->id)->delete(); 
		$request->session()->flash('message','Role deleted successfully.');
      return Response()->json(['status'=>200]);
    }

}