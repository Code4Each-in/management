<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Country;
use App\Models\Projects;
use Illuminate\Http\Request;
use App\Models\Users;
use Illuminate\Support\Facades\Hash;

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
        $user = Users::create([
            'first_name' => $request->input('name'),
            'last_name' => '',
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'salary' => null,
            'employee_id' => null,
            'address' => $request->input('address'),
            'city' => $request->input('city'),
            'designation' => 'Client',
            'state' => null,
            'status' => 1,
            'zip' => $request->input('zip'),
            'phone' => $request->input('phone'),
            'department_id' => null,
            'role_id' => 6,
            'joining_date' => now(),
            'birth_date' => $request->input('birth_date'),
            'profile_picture' => null,
            'created_at' => now(),
            'updated_at' => now(),
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

