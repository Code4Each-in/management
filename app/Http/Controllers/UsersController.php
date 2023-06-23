<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Users;
use App\Models\Departments;
use App\Models\Roles;
use App\Models\Managers;
use App\Models\UserDocuments;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{

	/**
	 * 
	 * @return \Illuminate\View\View
	 */
	public function index()
	{

		if (auth()->user()->role_id==env('SUPER_ADMIN'))
		{
			$usersData = Users::where('users.role_id','!=',env('SUPER_ADMIN'))->orderBy('id','desc')->get();
		}elseif (auth()->user()->role->name == 'HR Manager') {
			$usersData = Users::where('users.role_id','!=',env('SUPER_ADMIN'))->where('users.role_id','!=',auth()->user()->id)->orderBy('id','desc')->get();
			
		}
		else
		{
		$usersData = Users::join('managers', 'users.id', '=', 'managers.user_id')->where('managers.parent_user_id',auth()->user()->id)->get([ 'managers.user_id','users.*']);
		}
		//database query
		$users_Data=Users::with('role','department')->where('status','!=',0)->orderBy('id','desc')->get();  //database query
		// dd($users_Data);
		$roleData=Roles::orderBy('id','desc')->get();//database query
		$departmentData = Departments::orderBy('id','desc')->get();
		// dd($departmentData);
		return view('users.index',compact('usersData','roleData','departmentData','users_Data'));
	}
	/**
	* Store a newly created resource in storage.
	*
	* @param  \Illuminate\Http\Request  $request
	* @return \Illuminate\Http\Response
	*/
	public function store(Request $request)   //  validations 
	{	
		$validator = \Validator::make($request->all(), [
		'user_name' => 'required', 
		'last_name'=>'required', 
		'email'=>'required|unique:users', 
		'password'=>'required|confirmed|min:8', 
		'phone'=>'required|unique:users', 
		'joining_date'=>'required', 
		'birth_date'=>'required', 
		'profile_picture'=>'required|image|mimes:jpg,png,jpeg,gif', 
		'role_select'=>'required', 
		'department_select'=>'required', 
		'address'=>'required', 

		]);

		if ($validator->fails())
		{
			return response()->json(['errors'=>$validator->errors()->all()]);
		}

		$validate = $validator->valid(); //getting all data from db
		$profilePicture = time().'.'.$validate['profile_picture']->extension(); 
		$validate['profile_picture']->move(public_path('assets/img/profilePicture'), $profilePicture);
		$path ='profilePicture/'.$profilePicture;

		$salaried=null;	 
		if (isset($validate['salaried'])) 
		{
			$salaried = $validate['addsalary'];
		}			
		$users =Users::create([
			'first_name' => $validate['user_name'],
			'last_name' => $validate['last_name'], 
			'email' => $validate['email'],
			'password' => $validate['password'],
			'salary'=>$salaried ,
			'employee_id' => $validate['employee_id'],
			'address'=>$validate['address'],
			'city' => $validate['city'],
			'state' => $validate['state'],
			'zip' => $validate['zip'],
			'phone'=>$validate['phone'],
			'department_id'=>$validate['department_select'],
			'role_id'=>$validate['role_select'],
			'phone'=>$validate['phone'],
			'joining_date'=>$validate['joining_date'],
			'birth_date'=>$validate['birth_date'],					
			'profile_picture'=>$path,						
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		]);

		if (isset($validate['manager_select']))
		{			
			foreach($validate['manager_select'] as $manager)
			{				
				$managers =Managers::create([					
					'user_id' => $users->id,
					'parent_user_id' => $manager,
				]);
			}		
		}		
		$request->session()->flash('message','User added successfully.');
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
		
		$managerSelectOptions = Users::where('id','!=',$request->id)->where('status','!=',0)->get();
		// dd($managerSelectOptions);
		$Managers = Managers::where(['user_id' => $request->id])->get();

		return Response()->json(['users' =>$users, 'Managers' =>$Managers,'managerSelectOptions' =>$managerSelectOptions]);
	}                                 


	/**
	* Update.
	*
	* @param  \Illuminate\Http\Request  $request
	* @return \Illuminate\Http\Response 
	*/
	public function update(Request $request){     // validation
		// dd($request->users_id);
		
		$validator = \Validator::make($request->all(), [
			'edit_username' => 'required',
			'edit_lastname' => 'required',
			'edit_email'=>'required',
			'edit_phone'=>'required',
			'edit_joining_date'=>'required',
			'edit_birthdate'=>'required',					
			'role_select'=>'required',
			'department_select'=>'required',
			'address'=>'required',
			'edit_password' => 'confirmed',
		]);

		if ($validator->fails())
		{
			return response()->json(['errors'=>$validator->errors()->all()]);
		}

		$validate = $validator->valid();

		$usersData = Users::find($request->users_id);
		if (isset($request['edit_profile_picture'])){
		$profilePicture = time().'.'.$request['edit_profile_picture']->extension(); 
		$request['edit_profile_picture']->move(public_path('assets/img/profilePicture'), $profilePicture);
		$path ='profilePicture/'.$profilePicture;
		}
		$salaried=null;		 
		if (isset($validate['edit_salaried'])) 
		{
			$salaried = $validate['edit_salary'];
		}
		// $eta= $request['eta'];
		$UpdateUserArr= [
			'first_name' => $validate['edit_username'],        
			'last_name' => $validate['edit_lastname'],
			'email' => $validate['edit_email'],
			'phone' => $validate['edit_phone'],
			'joining_date' => $validate['edit_joining_date'],
			'birth_date' => $validate['edit_birthdate'],
			// 'eta'=>$request['eta'],
			'salary' =>$salaried,
			'employee_id' => $validate['edit_employee_id'],
			'role_id'=> $validate['role_select'],
			'department_id'=>$validate['department_select'],
			'address'=>$validate['address'],
			'city' => $validate['edit_city'],
			'state' => $validate['edit_state'],
			'zip' => $validate['edit_zip'],
			];

			if (isset($validate['edit_password'])){
				$UpdateUserArr['password'] = Hash::make($validate['edit_password']);
			}
			
		if (isset($path)){
			$UpdateUserArr['profile_picture']=$path;
		}
		
		Users::where('id',$request->users_id)  
		->update($UpdateUserArr);

		if (isset($validate['manager_select'])){	
			$checkManagersExist=Managers::where(['user_id' =>$request->users_id])->get(); 

		if (!empty($checkManagersExist)){
			Managers::where('user_id', $request->users_id)->delete();			
		}		
		foreach($validate['manager_select'] as $updatemanager)
		{			
			$managers =Managers::create([					
			'user_id' => $request->users_id,
			'parent_user_id' => $updatemanager,
			]);
		}		
		}
		if (isset($request['edit_profile_picture'])){
		}
		$request->session()->flash('message','User updated successfully.');
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
		$request->session()->flash('message','User deleted successfully.');
		return Response()->json($Users);
	}

	public function updateUserStatus(Request $request)
	{		 
		Users::where('id', $request->dataId)
		->update([
		'status' => $request->status
		]);
		$request->session()->flash('message','User status updated  successfully.');
		return Response()->json(['status'=>200]);	
	}

	// GET LOGIN USER PROFILE DETAILS
	public function Userprofile(Request $request){
		$usersProfile=Users::with('role','department')->where('id',auth()->user()->id)->first();
		
		$documents = UserDocuments::where('user_id', $usersProfile->id)->get();
		
		return view('profile.index',compact('usersProfile','documents'));
	}

	// UPDATE LOGIN USER PROFILE 
	public function updateProfile(Request $request){
		$validator = \Validator::make($request->all(), [
			// 'first_name' =>'required',
			// 'last_name' =>'required',
			// 'email' =>'required',
			// 'phone'=>'required',
			// 'joining_date'=>'required',
			// 'birth_date'=>'required',
			// 'address'=>'required',					
			// 'city'=>'required',
			// 'state'=>'required',
			// 'zip'=>'required',
			'tshirt_size' => 'nullable',
			'skills' => 'nullable',
		]);
		// dd($request->tshirt_size);
		if ($validator->fails())
		{
			return response()->json(['errors'=>$validator->errors()->all()]);
		}
		$validate = $validator->valid();
		Users::where('id',$request->user_id)
			->update([
			'skills' => $validate['skills'], 
			'tshirt_size' => $validate['tshirt_size'],        
		]);
		return Response()->json(['status'=>200, 'message' => 'Your Profile updated successfully.', 'user_profile_data'=>$validate]);
	}
	// UPDATE PROFILE PICTURE OF LOGIN USER
	public function updateProfilePicture(Request $request){
		$validator = \Validator::make($request->all(), [	
			'edit_profile_input'=>'image|mimes:jpg,png,jpeg,gif'				
		],
		[
            'edit_profile_input.image' => 'The profile picture must be an image.',
            'edit_profile_input.mimes' => 'The profile picture must be a file of type: jpg, png, jpeg, gif.'
        ]
		);
		if ($validator->fails())
		{
			return response()->json(['errors'=>$validator->errors()->all()]);
		}
		$validate = $validator->valid();
		$profilePicture = time().'.'.$validate['edit_profile_input']->extension(); 
		$validate['edit_profile_input']->move(public_path('assets/img/profilePicture'), $profilePicture);
		$path ='profilePicture/'.$profilePicture;
		
		Users::where('id', $request->user_id)->update(['profile_picture'=>$path]);

		return Response()->json(['status'=>200, 'message' => 'Profile Picture updated successfully.', 'path'=>url('assets/img/profilePicture/'.$profilePicture)]);
	}
	public function changeUserPassword(Request $request){

		$validator = \Validator::make($request->all(),[ 
			'password' => 'required', 
			'new_password'=>'required|confirmed|min:8'
			]);

		if ($validator->fails()){
			return response()->json(['errors'=>$validator->errors()->all()]);
		}
		
		$validate = $validator->valid();

		$user = Users::findOrFail($request->user_id);
		if (Hash::check($request->password,$user->password)){
			$user->fill([
			'password'=> $request->new_password
			])->save();	
		}
		else
		{
			return response()->json(['error'=>'password does not match']);
		}
	
		return Response()->json(['status'=>200, 'message' => 'Profile Password updated successfully.']);
	}	
	public function deleteProfilePicture(Request $request)
	{
		$users=Users::where('id', $request->profileId)
			->update([
			'profile_picture' => null,
		]);
		return Response()->json(['status'=>200, 'message' => ' Profile picture deleted successfully.']);
	}


	public function saveCropedProfilePicture(Request $request)
    {
        $folderPath = public_path('assets/img/profilePicture/');
        $image_parts = explode(";base64,", $request->image);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
 
        $imageName = uniqid() . '.png';
		$path ='profilePicture/'.$imageName;
 
        $imageFullPath = $folderPath.$imageName;
 
        file_put_contents($imageFullPath, $image_base64);
		$userpath = Users::where('id', $request->user_id)->update(['profile_picture'=>$path]);
		if($userpath){
			return response()->json(['success'=>'Croped Image Uploaded Successfully','path' => $path]);
		}
    }


	public function uploadDocument(Request $request)
	{
		$validator = \Validator::make($request->all(),[ 
			'user_id' => 'required', 
			'document_name' => 'required',
			'upload_document' => 'required|file|mimes:jpg,jpeg,png,pdf,doc|max:2048'
			]);

		if ($validator->fails()){
			return response()->json(['errors'=>$validator->errors()->all()]);
		}
		
		$validate = $validator->valid();

		if (isset($request['upload_document'])) {
			$user_id = $validate['user_id'];
			$userDetail = User::find($user_id);
			$document_name = $validate['document_name'];
			$documentName = str_replace(' ', '', $document_name);
		
			if ($request->hasFile('upload_document')) {
				$file = $request->file('upload_document');
				$ext = strtolower($file->getClientOriginalExtension()); // Get the file extension in lowercase
				$allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc']; // Allowed file extensions
		
				if (in_array($ext, $allowedExtensions)) {
					$document_title = ucfirst($userDetail->first_name) . '_' . $user_id . '_' . time().'_'. $documentName . '.' . $ext;
					$file->move(public_path('assets/img/userDocuments'), $document_title);
		
					$path = 'userDocuments/' . $document_title;
		
					$document = new UserDocuments();
					$document->user_id = $validate['user_id'];
					$document->document_title = $validate['document_name'];
					$document->document_link = $path;
					$document->save();
				} else {
					// Handle case when the file extension is not allowed
					return response()->json(['error' => 'Invalid file extension. Allowed extensions: jpg, jpeg, png, pdf, doc'], 400);
				}
			}
		}

		return Response()->json(['status'=>200, 'message' => 'Document Uploaded successfully.','document' => $document]);
	}

	// public function userUploadedDocuments()
	// {

	// 	// Retrieve the documents from the database
	// 	$documents = UserDocuments::all();

	// 	// Return the documents as JSON response
	// 	return response()->json($documents);

	// }

	public function deleteProfileDocument(Request $request)
	{

		$document = UserDocuments::where('id',$request->documentId)->delete();
        $request->session()->flash('message','Document deleted successfully.');

		return Response()->json(['status'=>200 ,'documents' => $document]);
	}
}