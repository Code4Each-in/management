<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\UserLeaves;
use Carbon\Carbon;
use App\Http\Requests\StoreUserLeavesRequest;
use Illuminate\Support\Facades\Input;
use App\Models\Roles;


class LeavesController extends Controller
{
    public function index()
    {
        $leavesData = UserLeaves::orderBy('id','desc')->get();
		$roleData=Roles::where(['id' => auth()->user()->role_id])->first();
        // dd($roleData);
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
		
        UserLeaves::where(['id'=>$request->LeavesId, 'user_id'=> auth()->user()->id])
			->update([
            'is_approved' =>$request->LeavesStatus
			 ]);
            $message= "user leave dissapproved";     
            if ($request->LeavesStatus==1) {

                $message="user leave approved";

            }
         

			 $request->session()->flash('message',   $message);
		     return Response()->json(['status'=>200]);	
	 }
	 
}