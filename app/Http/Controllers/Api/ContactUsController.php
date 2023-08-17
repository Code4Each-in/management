<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\WebsiteContactUs;
 
class ContactUsController extends Controller
{
    public function contactUs(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required',       
            'email' => 'required|email',           
            'note' => 'required',       
        ]);
 
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
        $validate = $validator->valid();
        $WebsiteContactUs =WebsiteContactUs::create([
            'name' => $validate['name'],
            'email' => $validate['email'],
            'phone' => $validate['phone'],
            'message' => $validate['note'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return Response()->json(['status'=>200, 'contactus'=>$WebsiteContactUs]);
    }
}