<?php

namespace App\Http\Controllers;

use App\Models\Devices;
use Illuminate\Http\Request;

class DevicesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $devices = Devices::all();
        return view('devices.index', compact('devices'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'deviceName' => 'required', 
            'deviceModel' => 'nullable',
            'brand' => 'nullable',
            'buyingDate' => 'required',  
        ]);        

        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }

        $deviceName = $request->get('deviceName');
        $deviceModel = $request->get('deviceModel');
        $brand = $request->get('brand');
        $buyingDate = $request->get('buyingDate');
        
        $device = Devices::Create([
            'name' => $deviceName,
            'device_model' => $deviceModel,
            'brand' => $brand,
            'buying_date' => $buyingDate,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

		$request->session()->flash('message','Device added successfully.');
        return Response()->json(['status'=>200, 'device'=>$device]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Devices  $devices
     * @return \Illuminate\Http\Response
     */

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Devices  $devices
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $device  = Devices::where(['id' => $request->id])->first();
        return Response()->json(['device' =>$device]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Devices  $devices
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'edit_device_name' => 'required',   
            'edit_device_model' => 'nullable',   
            'edit_buying_date' => 'required',   
            'edit_brand' => 'nullable',   
        ]);
 
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
		
        Devices::where('id', $request->id)
        ->update([
            'name' => $request->edit_device_name,
            'device_model' => $request->edit_device_model,
            'buying_date' => $request->edit_buying_date,
            'brand' => $request->edit_brand,
        ]);
		$request->session()->flash('message','Device updated successfully.');
        return Response()->json(['status'=>200]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Devices  $devices
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $device = Devices::where('id',$request->id)->delete();
        $request->session()->flash('message','Device deleted successfully.');
       return Response()->json($device);
    }
}
