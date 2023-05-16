<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\UserLeaves;
use Carbon\Carbon;
use App\Http\Requests\StoreUserLeavesRequest;
use App\Mail\LeaveRequestMail;
use App\Mail\LeaveStatusMail;
use Illuminate\Support\Facades\Input;
use App\Models\Roles;
use App\Models\Managers;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyMail;
use App\Models\Users;

class LeavesController extends Controller
{
    public function index()
    {
        $leavesData = UserLeaves::orderBy('id','desc')->where('user_id',auth()->user()->id)->get();
		$roleData=Roles::where(['id' => auth()->user()->role_id])->first();
        return view('leaves.index',compact('leavesData', 'roleData'));  

    }  
    public function store(StoreUserLeavesRequest $request)
    {
        if (isset($request->validator) && $request->validator->fails()) {
            return response()->json(['errors'=>$request->validator->errors()->all()]);
        } 
        $userLeaves=UserLeaves::create([     
            'user_id'=> auth()->user()->id,     
            'from'=>$request->from,
            'to'=>$request->to,
            'type'=>$request->type,
            'notes'=>$request->notes,
           ]);    

           $userObj = UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')
           ->where('user_leaves.id', '=',  $userLeaves->id)
           ->select(['user_leaves.*', 'users.email', 'users.first_name', 'users.last_name', 'users.role_id'])
           ->first();
           $data = $userObj;
           $subject = "Leave Application - ".ucfirst($userObj->first_name)." ".ucfirst($userObj->last_name);
           $data->subject = $subject;
           if($userLeaves){
           $id = $userLeaves->user_id;
           $user = Users::find($id);
           $role_id = $user->role_id;
           $roles =Roles::select('*')->where('id', '=',$role_id)->first();
           if($roles->name == "Employee")
           {
    		    $managersData = Users::join('managers', 'users.id', '=', 'managers.parent_user_id')->where('managers.user_id',auth()->user()->id)->get([ 'managers.user_id','users.email']);
                // Gets the Only Emails of managers Related to user with pluck method
                $managerEmails = $managersData->pluck('email');
                $rolesData = Users::join('roles', 'users.role_id', '=', 'roles.id')
                ->select('users.id', 'users.email')
                ->whereIn('roles.name', ['Super Admin', 'HR Manager'])
                ->get();
                $roleEmails = $rolesData->pluck('email');
                // Merging mail collection data in one collection 
                $emails = $managerEmails->merge($roleEmails);
                Mail::to($emails)->send(new LeaveRequestMail($data));
                
           }elseif ($roles->name == "Manager") {
            
            $managersData = Users::join('managers', 'users.id', '=', 'managers.parent_user_id')->where('managers.user_id',auth()->user()->id)->get([ 'managers.user_id','users.email']);
            // Gets the Only Emails of managers Related to user with pluck method
            $managerEmails = $managersData->pluck('email');
            $rolesData = Users::join('roles', 'users.role_id', '=', 'roles.id')
            ->select('users.id', 'users.email')
            ->whereIn('roles.name', ['Super Admin', 'HR Manager'])
            ->get();
            $roleEmails = $rolesData->pluck('email');
            // Merging mail collection data in one collection 
            $emails = $managerEmails->merge($roleEmails);
            Mail::to($emails)->send(new LeaveRequestMail($data));
            
           }elseif ($roles->name == "HR Manager") {

            $managersData = Users::join('managers', 'users.id', '=', 'managers.parent_user_id')->where('managers.user_id',auth()->user()->id)->get([ 'managers.user_id','users.email']);
            // Gets the Only Emails of managers Related to user with pluck method
            $managerEmails = $managersData->pluck('email');
            $rolesData = Users::join('roles', 'users.role_id', '=', 'roles.id')
            ->select('users.id', 'users.email')
            ->whereIn('roles.name', ['Super Admin'])
            ->get();
            $roleEmails = $rolesData->pluck('email');
            // Merging mail collection data in one collection 
            $emails = $managerEmails->merge($roleEmails);
            Mail::to($emails)->send(new LeaveRequestMail($data));
    
           }
           $request->session()->flash('message','Leaves added successfully.');
        }

           return Response()->json(['status'=>200, 'leaves'=>$userLeaves]);
    }
    public function setLeavesApproved(Request $request)
	 {
      
        $userLeaves = UserLeaves::where(['id'=>$request->LeavesId])
			->update([
            'leave_status' =>$request->LeavesStatus,
            'status_change_by'=> auth()->user()->id,
          
			 ]);
             $userObj = UserLeaves::join('users', 'users.id', '=', 'user_leaves.user_id')
                        ->where('user_leaves.id', $request->LeavesId)
                        ->select('users.email','users.first_name','users.last_name','user_leaves.type','user_leaves.from' ,'user_leaves.notes','user_leaves.to','user_leaves.leave_status' )->first();
            $data = $userObj;
            $subject = "Leave Request - ".ucfirst($userObj->leave_status);
            $data->subject = $subject;
            $userEmail = $userObj->email;
             if($userLeaves > 0){
                Mail::to($userEmail)->send(new LeaveStatusMail($data));
             }

			 $request->session()->flash('message', 'user leave status updated' );
		     return Response()->json(['status'=>200]);	
	 }
     
     public function showTeamData()
	 {
        if (auth()->user()->role_id==env('SUPER_ADMIN'))
		{
            $teamLeaves= UserLeaves::join('users', 'user_leaves.user_id', '=', 'users.id')->orderBy('id','desc')->get(['user_leaves.*','users.first_name']);
        }
         else
         {
             $teamLeaves = UserLeaves::join('managers', 'user_leaves.user_id', '=', 'managers.user_id')->join('users', 'user_leaves.user_id', '=', 'users.id')->where('managers.parent_user_id',auth()->user()->id)->get(['user_leaves.*', 'managers.user_id','users.first_name']);
         }

        return view('leaves.team',compact('teamLeaves'));
	 }


}