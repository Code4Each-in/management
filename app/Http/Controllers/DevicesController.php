<?php

namespace App\Http\Controllers;

use App\Models\Devices;
use App\Models\DevicesDocuments;
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
        $devices = Devices::orderBy('id','desc')->get();
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
            'deviceModel' => 'required',
            'brand' => 'required',
            'serialNumber' => 'nullable',
            'buyingDate' => 'nullable',  
            'add_document.*' => 'file|mimes:jpg,jpeg,png,doc,docx,xls,xlsx,pdf|max:5000',
        ]);        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }

        $deviceName = $request->get('deviceName');
        $deviceModel = $request->get('deviceModel');
        $brand = $request->get('brand');
        $serialNumber = $request->get('serialNumber');
        $buyingDate = $request->get('buyingDate');
        
        $device = Devices::Create([
            'name' => $deviceName,
            'device_model' => $deviceModel,
            'brand' => $brand,
            'serial_number' => $serialNumber,
            'buying_date' => $buyingDate,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if($request->hasfile('add_document')){
            foreach($request->file('add_document') as $file)
            {
            $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $dateString = date('YmdHis');
            $name = $dateString . '_' . $fileName . '.' . $file->extension();
            $file->move(public_path('assets/img/devicesAssets'), $name);  
            $path='devicesAssets/'.$name;
                $documents = DevicesDocuments::create([
                'document' => $path,
                'device_id'=> $device->id,
                ]); 
            }
       }

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
            'edit_device_model' => 'required',   
            'edit_buying_date' => 'nullable',   
            'edit_brand' => 'required',   
            'edit_serial_number' => 'nullable',   
            'edit_add_document.*' => 'file|mimes:jpg,jpeg,png,doc,docx,xls,xlsx,pdf|max:5000',
        ]);
 
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
        $device = Devices::where('id', $request->id)
        ->update([
            'name' => $request->edit_device_name,
            'device_model' => $request->edit_device_model,
            'buying_date' => $request->edit_buying_date,
            'serial_number' => $request->edit_serial_number,
            'brand' => $request->edit_brand,
        ]);

        if($request->hasfile('edit_add_document')){
            foreach($request->file('edit_add_document') as $file)
            {
            $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $dateString = date('YmdHis');
            $name = $dateString . '_' . $fileName . '.' . $file->extension();
            $file->move(public_path('assets/img/devicesAssets'), $name);  
            $path='devicesAssets/'.$name;
                $documents = DevicesDocuments::create([
                'document' => $path,
                'device_id'=> $request->id,
                ]); 
            }
       }

		$request->session()->flash('message','Device updated successfully.');
        return Response()->json(['status'=>200, 'device' => $device]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Devices  $devices
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $device = Devices::where('id',$request->id)->first();
        if($device->status == 1){
            $request->session()->flash('error','You Cannot Delete In Use Device .');
        }else{
            $device->delete();
            $request->session()->flash('message','Device deleted successfully.');
        }
       return Response()->json($device);
    }


    public function show($id)
    {
        $device = Devices::find($id); 
        $deviceDocuments = DevicesDocuments::where('device_id',$id)->get();
        return view('devices.show',compact('device','deviceDocuments'));
    }


    public function deleteDocument(Request $request)
	{
		$document = DevicesDocuments::where('id',$request->documentId)->delete();
        $request->session()->flash('message','Document deleted successfully.');

		return Response()->json(['status'=>200 ,'documents' => $document]);
	}
}
