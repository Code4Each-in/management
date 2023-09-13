<?php
 
namespace App\Http\Controllers\Api;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\WebsiteContactUs;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactUsMail;
use App\Models\ApiAccessTokens;

class ContactUsController extends Controller
{
    public function contactUs(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required',       
            'email' => 'required|email',           
            'note' => 'required',       
            'phone' => 'required',       
            'ipaddress' => 'required',       
        ]);
 
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
        $validate = $validator->valid();
        $websiteContactUs =WebsiteContactUs::create([
            'name' => $validate['name'],
            'email' => $validate['email'],
            'phone' => $validate['phone'],
            'message' => $validate['note'],
            'ipaddress' =>  $validate['ipaddress'],  
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $data = $websiteContactUs;
        $subject = "Contact us from Code4Each website";
        $data->subject = $subject;
        // Mail::to(array("info@code4each.com", "hr@code4each.com"))->send(new ContactUsMail($data));

        return Response()->json(['status'=>200, 'contactus'=>$websiteContactUs]);
    }

    public function generateToken(Request $request)
    {
        $ipAddress = $request->ip();
        $count = WebsiteContactUs::where('ipaddress', $ipAddress)->count();
        if($count <= 5){
            $record = ApiAccessTokens::select('token')->where('ipaddress', '=',$ipAddress)->where('expire_date', '!=', date('Y-m-d'))->first();
            if(!empty($record) && isset($record->token)){
                $token =$record->token;
            }else{
                $token = bin2hex(random_bytes(32)); // Generate a random 64-character hexadecimal token
                ApiAccessTokens::create([
                    'token' => $token,
                    'ipaddress' =>  $ipAddress,
                    'expire_date' => date('Y-m-d', strtotime('+1 day')),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }           
        }else{
            return response()->json(['errors'=>'Not Allowed.']);
        }
        return response()->json(['token' => $token, "ipaddress"=> $ipAddress]);
    }
}