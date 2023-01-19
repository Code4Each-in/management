<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Departments;
 
class DepartmentsController extends Controller
{
    /**
     * 
     * @return \Illuminate\View\View
     */
	 //
    public function index()
    {
        $departmentData = Departments::all();
        return view('departments.index', compact('departmentData'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $departmentName = $request->get('departmentName');
        $department =Departments::create([
            'name' => $departmentName,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return Response()->json(['status'=>200, 'department'=>$department]);
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {   
        $department  = Departments::where(['id' => $request->id])->first();
        return Response()->json(['department' =>$department]);
    }

    /**
     * Update.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        Departments::where('id', $request->id)
        ->update([
            'name' => $request->name
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
        $Departments = Departments::where('id',$request->id)->delete();
      
        return Response()->json($Departments);
    }
    
}