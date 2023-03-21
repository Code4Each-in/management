<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\UserLeaves;
use Carbon\Carbon;
use App\Http\Requests\StoreUserLeavesRequest;
use Illuminate\Support\Facades\Input;
use App\Models\Roles;
use App\Models\Managers;


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
           $request->session()->flash('message','Leaves added successfully.');
           return Response()->json(['status'=>200, 'leaves'=>$userLeaves]);
    }
    public function setLeavesApproved(Request $request)
	 {
      
        UserLeaves::where(['id'=>$request->LeavesId])
			->update([
            'leave_status' =>$request->LeavesStatus,
            'status_change_by'=> auth()->user()->id,
          
			 ]);

         
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