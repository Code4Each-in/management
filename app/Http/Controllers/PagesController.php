<?php

namespace App\Http\Controllers;

use App\Models\Pages;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function index()
    {
        $parentPage = Pages::where('parent_id','0')->get();
        $pageData = Pages::with('parentpage')->orderBy('id','desc')->get();
        return view('pages.index', compact('pageData','parentPage'));
    }

    public function store(Request $request)
    {
		 $validator = \Validator::make($request->all(), [
            'pageName' => 'required',    
            'parentId' => 'nullable',   
        ]);
        

        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
        $pageName = $request->get('pageName');
        $parentId = $request->get('parentId');
        
        $page = Pages::create([
            'name' => $pageName,
            'parent_id' => $parentId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
		$request->session()->flash('message','page added successfully.');
        return Response()->json(['status'=>200, 'page'=>$page]);
    }

    public function edit(Request $request)
    {   
        $page  = Pages::where(['id' => $request->id])->first();
        return Response()->json(['page' =>$page]);
    }


    public function update(Request $request)
    {
		$validator = \Validator::make($request->all(), [
            'name' => 'required',     
            'parent_id' => 'nullable',  
        ]);
 
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
		
        Pages::where('id', $request->id)
        ->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
        ]);
		$request->session()->flash('message','Page updated successfully.');
        return Response()->json(['status'=>200]);
    }


    public function destroy(Request $request)
    {
         $pages = Pages::where('id',$request->id)->delete();
         $request->session()->flash('message','Page deleted successfully.');
        return Response()->json($pages);
    }

}
