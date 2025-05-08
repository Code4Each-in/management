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
            'name' => 'required',
            'email' => 'required|email|unique:clients,email',
            'password' => 'required|min:8',
        ]);
    
        $client = Client::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'secondary_email' => $request->input('secondary_email'),
            'additional_email' => $request->input('additional_email'),
            'gender' => $request->input('gender'),
            'phone' => $request->input('phone'),
            'birth_date' => $request->input('birth_date'),
            'address' => $request->input('address'),
            'city' => $request->input('city'),
            'status' => $request->input('status'), 
            'zip' => $request->input('zip'),
            'country' => $request->input('country'),
            'projects' => $request->input('projects'),
            'company' => $request->input('company'),
            'source' => $request->input('source'),
            'skype' => $request->input('skype'),
            'last_worked' => $request->input('last_worked'),
            // 'password' => $request->input('password'), 
        ]);

        $user = Users::create([
            'first_name' => $request->input('name'),
            'last_name' => '',
            'gender' => $request->input('gender'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'salary' => null,
            'employee_id' => null,
            'address' => $request->input('address'),
            'city' => $request->input('city'),
            'designation' => 'Client',
            'state' => null,
            'status' => $request->input('status'),
            'zip' => $request->input('zip'),
            'phone' => $request->input('phone'),
            'department_id' => null,
            'role_id' => 6, // Client role
            'joining_date' => now(),
            'birth_date' => $request->input('birth_date'),
            'profile_picture' => null,
            'client_id' => $client->id, 
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'user' => $user]);
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
            'name' => 'required',
            'email' => 'required|email',
            'edit_password' => 'confirmed',
            /*'phone' => ['regex:/^\d{5,15}$/']
        ], [
            'phone.regex' => 'The phone number must be between 5 and 15 digits.'*/
        ]);
        $client = Client::find($request->id);
    
        if ($client) {
            $client->update($request->all());
            $user = Users::where('client_id', $client->id)->first();
            if ($user) {
                $user->email = $request->email;
                $user->password = $request->edit_password;
                $user->gender = $request->gender;
                $user->save();
            }
    
            return "success";
        }
    }
    
    public function deleteClient(Request $request) {
        $deleteClient = Client::where('id', $request->id)->delete();
        $request->session()->flash('message', 'Client deleted successfully.');
        return response()->json(["status"=>200]);
    }
    

}

