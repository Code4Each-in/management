<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Roles;
use App\Models\RolePermission;

class RolesController extends Controller
{
     /**
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
		$roleData=Roles::all(); //database query
        return view('roles.index',compact('roleData'));
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
        Roles::where('id', $request->id)
        ->update([
            'name' => $request->name
        ]);
        return Response()->json(['status'=>200]);
    }
	 public function destroy(Request $request)
    {
        $Roles = Roles::where('id',$request->id)->delete();
      
        return Response()->json($Roles);
    }

}
