<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Users;
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
		$username = $request->username;
		$password = $request->password;
		//dd($username);
		
		if(!Auth::validate(array('first_name'=> $username, 'password'=> $password ))){
			  return redirect()->to('/');
              
		}else{
			
			
			return view('dashboard.index');
		}
	
          
  

        //$user = Auth::getProvider()->retrieveByCredentials($credentials);

       // Auth::login($user);

        //return $this->authenticated($request, $user);
		
		
	}
	
	
}
