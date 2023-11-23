<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Country;
use App\Models\Projects;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::all();
        $projects = Projects::all();
        $countries = Country::all();
        return view('clients.index', compact('clients', 'projects','countries'));
    }

    public function create()
    {
        $projects = Projects::orderBy('id','desc')->get();
        return view('clients.add', compact('projects'));
    }

    public function show($id)
    {
        $client = Client::find($id); 
        if ($client) {
            return view('clients.show', ['client' => $client]);
        } else {
            // Handle the case where the product with the given ID was not found
            return abort(404);
        }
    }
    
    public function store(Request $request)
    {
        
        $request->validate([
            'name' => 'required'
            // 'email' => 'email',
            /*'phone' => ['regex:/^\d{5,15}$/']
        ], [
            'phone.regex' => 'The phone number must be between 5 and 15 digits.'*/
        ]);
        
        if(Client::create($request->all())) {
            return "success";
        }

    }


    public function edit(Request $request, $id)
    {
        $getClient = Client::where('id', $id)->first();
    
        if(!empty($getClient)) {
            return $getClient;
        }
        
    }
    
    public function update(Request $request) {
        $request->validate([
            'name' => 'required'
            // 'email' => 'required|email',
            /*'phone' => ['regex:/^\d{5,15}$/']
        ], [
            'phone.regex' => 'The phone number must be between 5 and 15 digits.'*/
        ]);
    
        $client = Client::find($request->id);
    
        if ($client) {
            $client->update($request->all());
            return "success";
        }
    }
    
    public function deleteClient(Request $request) {
        $deleteClient = Client::where('id', $request->id)->delete();
        $request->session()->flash('message', 'Client deleted successfully.');
        return response()->json(["status"=>200]);
    }
    

}

