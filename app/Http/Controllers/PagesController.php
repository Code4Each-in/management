<?php

namespace App\Http\Controllers;

use App\Models\Pages;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function index()
    {
        // dd("index");
        $parentPage = Pages::where('parent_id','0')->get();
        $pageData = Pages::with('parentpage')->orderBy('id','desc')->get();
        // dd($pageData);
        return view('pages.index', compact('pageData','parentPage'));
    }

    public function store(Request $request)
    {
        // dd($request);
		 $validator = \Validator::make($request->all(), [
            'pageName' => 'required',    
            'parentId' => 'nullable',   
        ]);
        // dd($validator->pageName);
        

        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
        $pageName = $request->get('pageName');
        $parentId = $request->get('parentId');
        // dd($pageName);
        
        $page = Pages::create([
            'name' => $pageName,
            'parent_id' => $parentId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        // $pageRouteName = $this->routeNaming($page);
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
        ]);
 
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
		
        Pages::where('id', $request->id)
        ->update([
            'name' => $request->name
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



    public function routeNaming($page)
    {
        // dd($page);
        $pageName =  $page->name;
        $routesNames = [
            'Listing' => $pageName. ".index",
            'Add' => $pageName. ".add",
            'Edit' => $pageName. ".edit",
            'Show' => $pageName. ".show",
            'Delete' => $pageName. ".delete",
        ];
        dd($routesNames);

        $routeName = $request->route()->getName();
    } 
}
