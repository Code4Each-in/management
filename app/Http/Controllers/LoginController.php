<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Users;
use App\Models\ClientAccessRequest;
use Illuminate\Support\Facades\Mail;
use App\Mail\ClientAccessRequested;


class LoginController extends Controller
{
    /**
     * Display login page.
     * 
     * @return Renderable
     */

    public function show()
    {
        $userId = request()->user()->id ?? null;
        if ($userId) {
            return redirect()->route('dashboard.index');
        } else {
            return view('auth.login');
        }
    }

	public function login(Request $request)
{
    if ($request->isMethod('get')) {
        $userId = $request->user()->id ?? null;
        return $userId ? redirect()->route('dashboard.index') : view('auth.login');
    }

    if ($request->isMethod('post')) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        $credentials['email'] = trim($credentials['email']);
        $credentials['password'] = trim($credentials['password']);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

           if ($user->role_id == 6 && $user->status != 1) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('client.inactive', ['user' => $user->id])
                                ->with('request_sent', false); 
            }


            $request->session()->regenerate();
            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'credentials_error' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }
}



public function inactiveClient(Request $request)
{
    $userName = session('inactive_user_name');
    if (!$userName) {
        return redirect()->route('login');
    }

    return view('auth.inactive-client', ['userName' => $userName]);
}

public function requestAccess(Request $request)
{
    $user = Users::findOrFail($request->user_id);

    $alreadyRequested = ClientAccessRequest::where('user_id', $user->id)
        ->where('is_approved', false)
        ->exists();

    if (!$alreadyRequested) {
        ClientAccessRequest::create([
            'user_id' => $user->id,
        ]);

        $admins = Users::where('role_id', 1)->get();
          $messages = [
            "greeting-text" => "New Client Access Request",
            "subject" => "Client Access Request From - {$user->first_name} {$user->last_name}",
            "title" => "A client has requested access to the platform. Details are below:",
            "body-text" => "Please log in to the admin panel to approve or reject the request.",
            "url-title" => "View Requests",
            "url" => "/admin/client-access-requests",
        ];
        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new ClientAccessRequested($user, $messages));
        }
    }
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('client.request-sent');
}


public function showInactiveClient(Users $user)
{
    if (session('request_sent')) {
        return view('auth.inactive-request-sent');
    }
    return view('auth.inactive-client', compact('user'));
}

	
	public function logOut() 
    {
        Session::flush();
        Auth::logout();
        return Redirect('/');
    }	
}