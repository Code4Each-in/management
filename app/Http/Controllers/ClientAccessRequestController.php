<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClientAccessRequest;
use App\Models\Users;
use Illuminate\Support\Facades\Mail;
use App\Mail\ClientAccessApproved; 

class ClientAccessRequestController extends Controller
{
    public function index()
{
    $requests = ClientAccessRequest::with('user')->latest()->get();
    return view('auth.client-access-requests', compact('requests'));
}


public function approve($id)
{
    $request = ClientAccessRequest::findOrFail($id);
    $request->is_approved = true;
    $request->save();

    $user = Users::find($request->user_id);
    if ($user && $user->client_id) {
        \App\Models\Client::where('id', $user->client_id)->update(['status' => 1]);
    }

    if ($user && $user->email) {
        Mail::to($user->email)->send(new ClientAccessApproved($user));
    }

    return back()->with('success', 'Client access approved.');
}


public function destroy($id)
{
    ClientAccessRequest::findOrFail($id)->delete();
    return back()->with('success', 'Client access request deleted.');
}
}
