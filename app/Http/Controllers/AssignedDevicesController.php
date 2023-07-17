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
        $assignedDeviceFilter = request()->all() ;
        $allDevicesFilter = $assignedDeviceFilter['all_devices'] ?? '';

        //Get Free Devices For Assign
        $devices = Devices::where('status',0)->get();
        //Get Users Without Admin To Assign Device Where Users Are Active 
        $users = Users::with('department')->whereHas('role', function($q){
            $q->where('name', '!=', 'Super Admin');
        })->where('status',1)->get();
        if ($allDevicesFilter == 'on') {
        $assignedDevices = AssignedDevices::with('user','device')->orderBy('id','desc')->get();
        } else {
        $assignedDevices = AssignedDevices::with('user','device')->where('status',1)->orderBy('id','desc')->get();

        }
        

        return view('devices.assigned.index', compact('devices','users','assignedDevices','allDevicesFilter'));
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
            // 'assigned_from' => 'required',
            // 'assigned_to' => 'nullable|after_or_equal:assigned_from|before_or_equal:today',  
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
            'from' => date('y-m-d'),
            // 'to' => $validate['assigned_to'],
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
    // public function edit($id)
    // {
    //     $assignedDevice = AssignedDevices::with('user','device')->where('id', $id)->first();
    //       //Get Free Devices For Assign
    //       $devices = Devices::all();
    //     $freeDevices = [];
    //     $inUseDevices = [];

    //     foreach ($devices as $device) {
    //         if ($device->status == 0) {
    //             $freeDevices[] = $device;
    //         } else {
    //             $inUseDevices[] = $device;
    //         }
    //     }
    //       //Get Users Without Admin To Assign Device Where Users Are Active 
    //       $users = Users::with('department')->whereHas('role', function($q){
    //           $q->where('name', '!=', 'Super Admin');
    //       })->where('status',1)->get();
    //     return view('devices.assigned.edit',compact('assignedDevice','freeDevices','inUseDevices','users'));
    // }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AssignedDevices  $assignedDevices
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'id' => 'required',   
            'status' => 'required',   
            // 'edit_assigned_from' => 'required',   
            // 'edit_assigned_to' => 'nullable|after_or_equal:edit_assigned_from|before_or_equal:today',      
        ]);
 
        if ($validator->fails())
        {
            // return response()->json(['errors'=>$validator->errors()->all()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $id = $request->id;
        $assignedDeviceData = AssignedDevices::find($id);
        $assignedDevice =  AssignedDevices::where('id', $id)
        ->update([
            'to' => date('Y-m-d'),
            'status' => $request->status,
        ]);
        $device_id =  $assignedDeviceData->device_id;
        if($assignedDevice){
            $deviceStatus = Devices::where('id', $device_id)->update([
                'status' => $request->status,
            ]);
        }
		
        $request->session()->flash('message','Assigned Device updated successfully.');
        return Response()->json(['status'=>200]);
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
        $request->session()->flash('error','You cannot delete an assigned device without recovering the device.');
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
