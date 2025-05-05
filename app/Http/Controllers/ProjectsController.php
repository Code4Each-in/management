<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ProjectAssigns;
use App\Models\ProjectFiles;
use App\Models\Projects;
use App\Models\Tickets;
use App\Models\Users;
use Illuminate\Support\Facades\Validator;
//use Dotenv\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\EmailNotification;
use Auth;
use App\Models\Feedback;
use App\Models\Sprint;

class ProjectsController extends Controller
{
 
    public function index()
    {
        $user = Auth::user();
        $clientId = $user->client_id;
        
    
        $users = Users::join('roles', 'users.role_id', '=', 'roles.id')
            ->whereNotIn('roles.name', ['Super Admin', 'HR Manager'])
            ->where('users.status', '!=', 0)
            ->where('users.role_id', '!=', 6)
            ->select('users.*', 'roles.name as role_name')
            ->orderBy('first_name', 'asc')
            ->get();
        if ($user->role_id == 6) {
            $projectIds = Projects::where('client_id', $clientId)
                ->pluck('id')
                ->toArray();
                
            $projects = Projects::where(function ($query) use ($clientId, $projectIds) {
                $query->whereIn('id', $projectIds);
                if (!is_null($clientId)) {
                    $query->where('client_id', $clientId); 
                }
            })->orderBy('id', 'desc')->get();
            $projectCount = $projects->count();
            $clients = Client::where('id', $clientId)
            ->orderBy('name', 'asc')
            ->get();
        } else {
            $projectsQuery = Projects::query();
            if (!is_null($clientId)) {
                $projectsQuery->where('client_id', $clientId);
            }
            $projects = $projectsQuery->orderBy('id', 'desc')->get();
            $projectCount = $projects->count();
            $clients = Client::orderBy('name', 'asc')->get();
        }

        foreach ($projects as $key => $data) {
            $projectAssigns = ProjectAssigns::join('users', 'project_assigns.user_id', '=', 'users.id')
                ->where('project_id', $data->id)
                ->orderBy('project_assigns.id', 'desc')
                ->get(['project_assigns.*', 'users.first_name', 'users.profile_picture']);
    
            $clientName = Client::where('id', $data->client_id)->pluck('name')->first();
    
            $projects[$key]->projectassign = $projectAssigns ?: null;
            $projects[$key]->client_name = $clientName;
        }
    
        return view('projects.index', compact('users', 'projects', 'clients', 'projectCount'));
    }
    

    public function create(Client $client)
    {
        return view('projects.create', compact('client'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'project_name' => 'required',
            'client_id' => 'required',
            'assign_to'=>'nullable',
            'live_url'=>'nullable|url',
            'dev_url'=>'nullable|url',
            'git_repo'=>'nullable|url',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status'=>'required', 
            'add_document.*' => 'file|mimes:jpg,jpeg,png,doc,docx,xls,xlsx,pdf|max:5000',
            ],[
                'add_document.*.file' => 'The :attribute must be a file.', 
                'add_document.*.mimes' => 'The :attribute must be a file of type: jpeg, png, pdf.',
                'add_document.*.max' => 'The :attribute may not be greater than :max kilobytes.',
                'add_document.*.max.file' => 'The :attribute failed to upload. Maximum file size allowed is :max kilobytes.',

            ]);

            $validator->setAttributeNames([
                'add_document.*' => 'document',
            ]);

            if ($validator->fails())
            {
                return response()->json(['errors'=>$validator->errors()->all()]);
            }
            
    		$validate = $validator->valid();
        $projects =Projects::create([
            'project_name' => $validate['project_name'],
            'client_id' => $validate['client_id'],
            'live_url' => $validate['live_url'],
            'dev_url' => $validate['dev_url'], 
            'git_repo' => $validate['git_repo'], 
            'tech_stacks' => $validate['tech_stacks'], 
            'start_date' => $validate['start_date'], 
            'end_date' => $validate['end_date'], 
            'description' => $validate['description'], 
            'credentials' => $validate['credentials'], 
            'status'=>$validate ['status'],
            'status_updated_by'=> auth()->user()->id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'user_id'=> auth()->user()->id,     
        ]);
        if (isset($validate['assign_to']))
        {				
            foreach($validate['assign_to'] as $manager)
            {				
                $manager =ProjectAssigns::create([					
                    'project_id' => $projects->id,
                    'user_id' => $manager,
                     'client_id' => $projects->client_id
                ]);
            }		
        }
        else {
            ProjectAssigns::create([
                'project_id' => $projects->id,
                'user_id' => null,
                'client_id' => $projects->client_id,
            ]);
        }
        if($request->hasfile('add_document')){
            foreach($request->file('add_document') as $file)
            {
            $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $dateString = date('YmdHis');
            $name = $dateString . '_' . $fileName . '.' . $file->extension();
            $file->move(public_path('assets/img/projectAssets'), $name);  
            $path='projectAssets/'.$name;
                $documents = ProjectFiles::create([
                'document' => $path,
                'project_id'=> $projects->id,
                ]); 
            }
       }

        $request->session()->flash('message','Project added successfully.');
    	return Response()->json(['status'=>200, 'projects'=>$projects]);

    }

    public function editProject($projectId)
    {
        $projects = Projects::with('client')->get(); // Load clients' data for each project
       $clients = Client::all(); // Load all clients for the dropdown
        // $projectsAssign = ProjectAssigns::where(['project_id' => $projectId])->get();  
        $users = Users::join('roles', 'users.role_id', '=', 'roles.id')
        ->where('roles.name','!=', 'Super Admin')
        ->where('roles.name','!=','HR Manager')
        ->where('users.status','!=',0)
        ->where('users.role_id', '!=', 6)
        ->select('users.*' ,'roles.name as role_name')
        ->orderBy('id','desc')
        ->get();
        $user = Auth::user();
        $clientId = $user->client_id;
        if ($user->role_id == 6) {
        $clients = Client::where('id', $clientId)
        ->orderBy('name', 'asc')
        ->get();
        }
        else{
            $clients = Client::all();
        }
        $projects = Projects::where(['id' => $projectId])->first();
        $userCount = Users::orderBy('id','desc')->where('status','!=',0)->get();
        $projectAssign = ProjectAssigns::with('user')->where('project_id',$projectId)->get();
        $ProjectDocuments= ProjectFiles::orderBy('id','desc')->where(['project_id' => $projectId])->get();

        return view('projects.edit',compact('projects','projectAssign','users','userCount','ProjectDocuments','clients'));
    }
    public function updateProject(Request $request ,$projectId)
    {
        // dd($request);
        $validator = Validator::make($request->all(),[
            'edit_projectname' => 'required',
            'edit_client_id'=>'required',
            'edit_liveurl'=>'nullable|url',
            'edit_devurl'=>'nullable|url',
            'edit_gitrepo'=>'nullable|url',
            'edit_startdate' => 'required|date',
            'edit_enddate' => 'nullable|date|after_or_equal:edit_startdate',
            'edit_status'=>'required', 
            'edit_document.*' => 'file|mimes:jpg,jpeg,png,doc,docx,xls,xlsx,pdf|max:5000',
            ],[
                'edit_document.*.file' => 'The :attribute must be a file.', 
                'edit_document.*.mimes' => 'The :attribute must be a file of type: jpeg, png, pdf.',
                'edit_document.*.max' => 'The :attribute may not be greater than :max kilobytes.',
                'edit_document.*.max.file' => 'The :attribute failed to upload. Maximum file size allowed is :max kilobytes.',

            ]);

            $validator->setAttributeNames([
                'edit_document.*' => 'document',
            ]);

            if ($validator->fails())
            {
                return response()->json(['errors'=>$validator->errors()->all()]);
            }
    		$validate = $validator->valid();

            if (!empty($request->edit_assign) && is_array($request->edit_assign)) {
                foreach ($request->edit_assign as $userId) {
                    ProjectAssigns::create([
                        'project_id' => $projectId,
                        'user_id' => $userId,
                        'client_id' => $validate['edit_client_id']
                    ]);
                }
            } else {
                ProjectAssigns::create([
                    'project_id' => $projectId,
                    'user_id' => null,
                    'client_id' => $validate['edit_client_id']
                ]);
            }

            $projects =Projects::where('id', $projectId)  
            ->update([
                'project_name' => $validate['edit_projectname'],
                'client_id' => $validate['edit_client_id'],
                'live_url' => $validate['edit_liveurl'],
                'dev_url' => $validate['edit_devurl'], 
                'git_repo' => $validate['edit_gitrepo'], 
                'tech_stacks' => $validate['edit_techstacks'], 
                'start_date' => $validate['edit_startdate'], 
                'end_date' => $validate['edit_enddate'], 
                'description' => $validate['description'], 
                'credentials' => $validate['credentials'], 
                'status'=>$validate ['edit_status'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),  
            ]);
            if($request->hasfile('edit_document')){
                foreach($request->file('edit_document') as $file)
                {
                $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $dateString = date('YmdHis');
                $name = $dateString . '_' . $fileName . '.' . $file->extension();
                $file->move(public_path('assets/img/projectAssets'), $name);  
                $path='projectAssets/'.$name;
                    $documents = ProjectFiles::create([
                    'document' => $path,
                    'project_id'=> $projectId,
                    ]); 
                }
               
           }
        $request->session()->flash('message','Project updated successfully.');
        return redirect()->back()->with('projects', $projects);
    }

    public function deleteProjectFile(Request $request)
    {
        
        $projectFile = ProjectFiles::where('id',$request->id)->forceDelete(); 
        if($projectFile){
            $request->session()->flash('message','ProjectFile deleted successfully.');
            return Response()->json(['status'=>200]); 
        }
    }

    public function DeleteProjectAssign(request $request)
    {
        $projectAssign = ProjectAssigns::where('id',$request->id)->delete();
        $request->session()->flash('message','ProjectAssign deleted successfully.');
        $AssignData = ProjectAssigns::where(['project_id' => $request->ProjectId])->get();
        
        $user = Users::whereHas('role', function($q){
            $q->where('name', '!=', 'Super Admin');
        })->orderBy('id','desc')->get()->toArray();	
    
       foreach($user as $key1=> $data1)
       {
           foreach($AssignData as $key2=> $data2){
               if($data1['id']==$data2['user_id']){
                   unset($user[$key1]);
               }
           }
       }
        return Response()->json(['status'=>200 ,'user' => $user,'AssignData' => $AssignData]); 
      
    }

    public function getProjectAssign(Request $request)
	{
        $projectAssigns= ProjectAssigns::join('users', 'project_assigns.user_id', '=', 'users.id')->where('project_id',$request->id)->orderBy('id','desc')->get(['project_assigns.*','users.first_name', 'users.profile_picture']);
       
        return Response()->json(['status'=>200, 'projectAssigns'=> $projectAssigns]);
    }
    public function showProject($projectId)
    {
        $projects = Projects::with('client')->find($projectId); 
        $projectAssigns= ProjectAssigns::join('users', 'project_assigns.user_id', '=', 'users.id')->where('project_id',$projectId)->orderBy('id','desc')->get(['project_assigns.*','users.first_name', 'users.profile_picture']);
        $ProjectDocuments= ProjectFiles::orderBy('id','desc')->where(['project_id' => $projectId])->get();
        return view('projects.show',compact('projects','projectAssigns','ProjectDocuments'));
    }
   

    public function devlisting()
    {
        $user = Auth::user();
        $clientId = $user->client_id;
        $projectIds = Projects::where('client_id', $clientId)->pluck('id');
        $ticketIds = Tickets::whereIn('project_id', $projectIds)->pluck('id');
        $developerIds = DB::table('ticket_assigns')
            ->whereIn('ticket_id', $ticketIds)
            ->distinct()
            ->pluck('user_id');
        $developers = Users::whereIn('id', $developerIds)->get();
        return view('developer.developer-listing', compact('developers'));
    }

        public function developerDetail($id)
    {
        $developer = Users::findOrFail($id);
        return view('developer.developer-detail', compact('developer'));
    }

    public function submitFeedback(Request $request)
    {
        $validate = $request->validate([
            'developer_id' => 'required|integer|exists:users,id', 
            'feedback' => 'required|string|max:1000',
        ]);
    
       
        $feedback = Feedback::create([
            'developer_id' => $validate['developer_id'],
            'feedback' => $validate['feedback'],
            'created_by' => auth()->id(), 
        ]);
        
        $authname = auth()->user()->first_name;
        
        if ($feedback) {
            try {
                $messages = [
                    "subject" => "New Feedback Received from - {$authname}",
                    "title" => "You've received new feedback from {$authname}.",
                    "body-text" => "Feedback: \"" . $validate['feedback'] . "\"",
                ];
        
                $assignedUser = Users::find($validate['developer_id']); 
        
                if ($assignedUser) {
                    $assignedUser->notify(new EmailNotification($messages));
                }
            } catch (\Exception $e) {
                \Log::error("Error sending notification for feedback: " . $e->getMessage());
            }
        }             
    
        return response()->json(['message' => 'Feedback submitted successfully!'], 200);
    }

    public function showSprints(Projects $project)
{
    
    $sprints = Sprint::where('project', $project->id)
    ->where('deleted_at', null)
    ->orderBy('id', 'desc')
    ->get();


    return view('developer.project-sprints', compact('project', 'sprints'));
}


public function allFeedback()
{
    $user = auth()->user();
    if ($user->role_id == 1) {
        $feedbacks = Feedback::with(['developer', 'client'])->latest()->get();
    } else {
        
        $feedbacks = Feedback::where('developer_id', $user->id)
                            ->with(['developer', 'client']) 
                            ->latest()
                            ->get();
    }

    return view('developer.feedback', compact('feedbacks'));
}

public function deleteproject(Request $request)
{
    $projectId = $request->id;

    $project = Projects::find($projectId);

    if ($project) {
        $project->delete();

        return response()->json(['status' => 200]);
    }

    return response()->json(['status' => 404, 'message' => 'Project not found']);
}



}
