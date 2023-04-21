<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Users;
use App\Models\Roles;

class LoginController extends Controller
{
    /**
     * Display login page.
     * 
     * @return Renderable
     */
    public function show()
    {
		
        return view('auth.login');
    }

	public function login(Request $request)
	{	
		 $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
 
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $role = Roles::where('id',auth()->user()->role_id)->first();
            auth()->user()->role_name = $role->name;
         
            return redirect()->intended('dashboard');
        }
 
        return back()->withErrors([
            'credentials_error' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
		
		
	}
	
	 public function logOut() {
        Session::flush();
        Auth::logout();
        return Redirect('/');
    }	
}