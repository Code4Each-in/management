<?php

namespace App\Http\Controllers;

use App\Models\AssignedDevices;
use App\Models\Devices;
use App\Models\Users;
use Illuminate\Http\Request;

class AssignedDevicesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        //Get Free Devices For Assign
        $devices = Devices::where('status',0)->get();
        //Get Users Without Admin To Assign Device Where Users Are Active 
        $users = Users::with('department')->whereHas('role', function($q){
            $q->where('name', '!=', 'Super Admin');
        })->where('status',1)->get();
        $assignedDevices = AssignedDevices::with('user','device')->get();

        return view('devices.assigned.index', compact('devices','users','assignedDevices'));
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
            'device_id' => 'required', 
            'user_id' => 'required',
            'assigned_from' => 'required',
            'assigned_to' => 'nullable|after_or_equal:assigned_from|before:today',  
        ]);        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
        $validate = $validator->valid();
        $status = 1;
        if ($request->assigned_to != null &&  $request->assigned_to == $request->assigned_from) {
            $status = 0;
        }
        $deviceassigned = AssignedDevices::Create([
            'device_id' => $validate['device_id'],
            'user_id' => $validate['user_id'],
            'from' => $validate['assigned_from'],
            'to' => $validate['assigned_to'],
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if($deviceassigned){
            Devices::where('id', $validate['device_id'])->update(
                [
                    'status' => $status,
                ]);
        }

		$request->session()->flash('message','Device Assigned successfully.');
        return Response()->json(['status'=>200, 'deviceassigned'=>$deviceassigned]);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AssignedDevices  $assignedDevices
     * @return \Illuminate\Http\Response
     */
    public function show(AssignedDevices $assignedDevices)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AssignedDevices  $assignedDevices
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $assignedDevice = AssignedDevices::with('user','device')->where('id', $id)->first();
          //Get Free Devices For Assign
          $devices = Devices::all();
        $freeDevices = [];
        $inUseDevices = [];

        foreach ($devices as $device) {
            if ($device->status == 0) {
                $freeDevices[] = $device;
            } else {
                $inUseDevices[] = $device;
            }
        }
          //Get Users Without Admin To Assign Device Where Users Are Active 
          $users = Users::with('department')->whereHas('role', function($q){
              $q->where('name', '!=', 'Super Admin');
          })->where('status',1)->get();
        return view('devices.assigned.edit',compact('assignedDevice','freeDevices','inUseDevices','users'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AssignedDevices  $assignedDevices
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        $validator = \Validator::make($request->all(), [
            'edit_device_id' => 'required',   
            'edit_user_id' => 'required',   
            'edit_assigned_from' => 'required',   
            'edit_assigned_to' => 'nullable|after_or_equal:edit_assigned_from|before:today',      
        ]);
 
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
        $status = 1;
		if ($request->edit_assigned_to != null) {
            $status = 0;
        }
      $assignedDevice =  AssignedDevices::where('id', $id)
        ->update([
            'device_id' => $request->edit_device_id,
            'user_id' => $request->edit_user_id,
            'from' => $request->edit_assigned_from,
            'to' => $request->edit_assigned_to,
            'status' => $status,
        ]);
        if($assignedDevice){
            $deviceStatus = Devices::where('id', $request->edit_device_id)->update([
                'status' => $status,
            ]);
        }
		
        $request->session()->flash('message','Assigned Device updated successfully.');
        return redirect()->route('devices.assigned.index')->with('assignedDevices', $assignedDevice);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AssignedDevices  $assignedDevices
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {   
        $device = AssignedDevices::where('id',$request->id)->first();
        if($device->to == null){
        $request->session()->flash('error','You Cannot Delete Assigned Device without Adding To Date.');
        }else{
            $device_id = $device->device_id;
            $device->status = 0;
            $device->delete();
            if ($device->deleted_at != null) {
                Devices::where('id', $device_id)->update(
                    [
                        'status' => 0,
                    ]);
            }
            $request->session()->flash('message','Assigned Device deleted successfully.');
        }
       return Response()->json($device);
    }
}
