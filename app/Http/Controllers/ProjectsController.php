<?php

namespace App\Http\Controllers;

use App\Models\ProjectAssigns;
use App\Models\ProjectFiles;
use App\Models\Projects;
use App\Models\Users;
use Illuminate\Http\Request;

class ProjectsController extends Controller
{
    public function index()
    {
        $users = Users::join('roles', 'users.role_id', '=', 'roles.id')
        ->where('roles.name','!=', 'Super Admin')->Where('roles.name','!=', 'Hr Manager')
        ->select('users.*', 'roles.name as role_name')->where('status','!=',0)->orderBy('id','desc')
        ->get();
        // dd($managers);
        $projects = Projects::orderBy('id','desc')->get(); 
        // if (!empty($projects)){
        //     $ticketStatus = Projects::join('users', 'tickets.status_changed_by', '=', 'users.id')
        // ->select('tickets.status','tickets.id as ticket_id','tickets.updated_at', 'users.first_name', 'users.last_name', )
        // ->get();
        foreach ($projects as $key=>$data) 
        {
            $projectAssigns= ProjectAssigns::join('users', 'project_assigns.user_id', '=', 'users.id')->where('project_id',$data->id)->orderBy('id','desc')->get(['project_assigns.*','users.first_name', 'users.profile_picture']);
            $projects[$key]->ticketassign = !empty($projectAssigns)? $projectAssigns:null;
        }

        return view('projects.index',compact('users','projects'));   
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(),[
            'project_name' => 'required',
            'user'=>'required',
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

		$end_date=null;	 
		if (isset($validate['end_date'])) 
		{
			$end_date = $validate['end_date'];
		}	
        $projects =Projects::create([
            'project_name' => $validate['project_name'],
            'live_url' => $validate['live_url'],
            'dev_url' => $validate['dev_url'], 
            'git_repo' => $validate['git_repo'], 
            'tech_stacks' => $validate['tech_stacks'], 
            'start_date' => $validate['start_date'], 
            'end_date' => $end_date, 
            'description' => $validate['description'], 
            'credentials' => $validate['credentials'], 
            'status'=>$validate ['status'],
            'status_updated_by'=> auth()->user()->id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'user_id'=> auth()->user()->id,     
        ]);
        if (isset($validate['manager']))
        {				
            foreach($validate['manager'] as $manager)
            {				
                $manager =ProjectAssigns::create([					
                    'project_id' => $projects->id,
                    'user_id' => $manager,
               
                ]);
            }		
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
        // $projectsAssign = ProjectAssigns::where(['project_id' => $projectId])->get();  
        $managers = Users::join('roles', 'users.role_id', '=', 'roles.id')
        ->where('roles.name', 'manager')
        ->select('users.*')->where('status','!=',0)->orderBy('id','desc')
        ->get();
        $projects = Projects::where(['id' => $projectId])->first();
        $projectAssign = ProjectAssigns::with('user')->where('project_id',$projectId)->get();
        $ProjectDocuments= ProjectFiles::orderBy('id','desc')->where(['project_id' => $projectId])->get();

        return view('projects.edit',compact('projects','projectAssign','managers','ProjectDocuments'));
    }
}
