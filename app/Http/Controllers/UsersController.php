<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Users;
use App\Models\Departments;
use App\Models\Roles;

class UsersController extends Controller
{
    /**
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
		$usersData=Users::all(); //database query
		$roleData=Roles::all(); //database query
		$departmentData = Departments::all();
        return view('users.index',compact('usersData','roleData','departmentData'));
    }
	 /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
	 public function store(Request $request)
    {	
		 $firstname = $request->get('userName');
		 $lastname = $request->get('lastname');
		 $email = $request->get('email');
		 $password = $request->get('password');
		 $salary = $request->get('salary');
		 $phone = $request->get('phone');
		 $address = $request->get('address');
		 $role_id = $request->get('role_id');
		 $department_id = $request->get('department_id');

         $users =Users::create([
            'first_name' => $firstname,
			'last_name' => $lastname,
			'email'=>$email, 
			'password'=>$password,
			'salary'=>$salary,
			'address'=>$address,
			'phone'=>$phone,
			'department_id'=>$department_id,
			'role_id'=>$role_id,	
			
			'phone'=>$phone,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return Response()->json(['status'=>200, 'users'=>$users]);
    }
	 /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
	 public function edit(Request $request)
    {   
	
        $users = Users::where(['id' => $request->id])->first();

        return Response()->json(['users' =>$users]);
    }
	
	
	 /**
     * Update.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
		public function update(Request $request)
    {
	
        Users::where('id', $request->id)
        ->update([
            'first_name' => $request->first_name,
			'last_name' => $request->last_name,
			'email' => $request->email,
			'phone' => $request->phone,
			'salary' => $request->salary,
			'role_id'=> $request->role,
			'department_id'=>$request->department,
			'address' => $request->address,
			'password' => $request->password,
			

        ]);
			
        return Response()->json(['status'=>200]);
    }
	  /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
	 public function destroy(Request $request)
    {
		
        $Users = Users::where('id',$request->id)->delete();
      
        return Response()->json($Users);
    }
	 public function updateUserStatus(Request $request)
	 {
		 
		  Users::where('id', $request->dataId)
			->update([
            'status' => $request->status
			 ]);
		        return Response()->json(['status'=>200]);	
	 }
	 
    
}