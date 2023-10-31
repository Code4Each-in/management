<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Projects;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::all();
        $projects = Projects::all();
        return view('clients.index', compact('clients', 'projects'));
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
            'email' => 'required|email',
            'phone' => ['required', 'regex:/^\d{5,15}$/'],
            'birth_date' => ['required', 'date', 'before:20 years ago'],
            'address' => 'required',
            'city' => 'required',
            'zip' => 'required',
            'state' => 'required'
        ], [
            'phone.regex' => 'The phone number must be between 5 and 15 digits.',
            'birth_date.date' => 'Invalid birth date.',
            'birth_date.before' => 'The person must be at least 20 years old.'
        ]);
        
        if(Client::create($request->all())) {
            return "success";
        }

    }


    public function edit(Request $request, $id)
{
    $request->validate([
        'name' => 'required',
        'email' => 'required|email',
        'phone' => ['required', 'regex:/^\d{5,15}$/'],
        'birth_date' => ['required', 'date', 'before:20 years ago'],
        'address' => 'required',
        'city' => 'required',
        'zip' => 'required',
        'state' => 'required'
    ], [
        'phone.regex' => 'The phone number must be between 5 and 15 digits.',
        'birth_date.date' => 'Invalid birth date.',
        'birth_date.before' => 'The person must be at least 20 years old.'
    ]);

    $client = Client::find($id);

    if ($client) {
        $client->update($request->all());
        return redirect()->route('clients.show', ['id' => $client->id])->with('success', 'Client updated successfully');
    } else {
        return back()->with('error', 'Client not found');
    }
}


public function destroy(Request $request, $id)
{
    $deleteClient = Client::where('id', $id)->delete();
    return redirect()->route('clients.index')->with('success', 'Client deleted successfully');
}
}
