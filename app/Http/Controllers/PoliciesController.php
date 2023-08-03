<?php

namespace App\Http\Controllers;

use App\Models\Policies;
use App\Models\PoliciesFiles;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Spatie\PdfToText\Pdf;
use thiagoalessio\TesseractOCR\TesseractOCR;

class PoliciesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $policies = Policies::with('PolicyDocuments')->orderBy('id','desc')->get(); 
        // dd($policies);
        return view('policies.index',compact('policies'));    
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */


    public function store(Request $request)
    {
        // dd($request);
        $validator = \Validator::make($request->all(),[
            'name' => 'required',
            'policy_text' => 'required',
            'pdf'   => 'required',
            'word' => 'nullable',
            'text' => 'nullable',
        ]);

            if ($validator->fails())
            {
                return response()->json(['errors'=>$validator->errors()->all()]);
            }
            
    		$validate = $validator->valid();
        $policies = Policies::create([
            'name' => $validate['name'],
            'policy_text' => $validate['policy_text'],  
        ]);
        $policy_id = $policies->id;
        if($policies){
            $policyDeatil = Policies::find($policy_id);
            $policyText = $policyDeatil->policy_text;
                if($request->pdf == 'on'){
                    // Initialize the PDF generator
                        $dompdf = new Dompdf();
            
                    // Load the editor text as HTML content
                    $dompdf->loadHtml($policyText);
            
                    // (Optional) Set paper size and orientation (e.g., A4 portrait)
                    $dompdf->setPaper('A4', 'portrait');
            
                    // // Render the PDF
                    $dompdf->render();
                    $originalName = $policyDeatil->name;
                    // Remove spaces from the original name
                    $trimfileName = str_replace(' ', '', $originalName);
                    // Generate a unique file name
                    $fileName = $trimfileName . time() . '.pdf';
                
                    // Save the PDF to the 'public' disk under the 'policyDocuments' directory
                    $result = Storage::disk('public')->put('policyDocuments/' . $fileName, $dompdf->output());
                    if($result){
                        $path =   'policyDocuments/' . $fileName;
                        $policyFile = PoliciesFiles::create([
                            'policy_id' => $policy_id ,
                            'document_name' => $fileName,
                            'document_type' => "PDF",
                            'document_link' => $path,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),  
                        ]);
                    }
                }
                if($request->word == 'on'){
                      
                // Initialize the PHPWord object
                $phpWord = new PhpWord();

                // Add a new section to the document
                $section = $phpWord->addSection();

                // Replace line breaks in $policyText with CRLF
                $policyText = str_replace(PHP_EOL, "\r\n", $policyText);

                // Add the editor text content as a paragraph
                $section->addText($policyText);

                $originalName = $policyDeatil->name;
                // Remove spaces from the original name
                $trimfileName = str_replace(' ', '', $originalName);
                // Generate a unique file name
                $fileName = $trimfileName . time() .  '.docx';

                // Save the Word document to a temporary file
                $writer = IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save(storage_path('app/' . $fileName));

                // // Save the Word document to a temporary file
                // $temporaryFilePath = storage_path('app/' . $fileName);
                // $writer->save($temporaryFilePath);

                // Now, save the Word document permanently using Laravel's Storage facade
                $destinationPath = 'policyDocuments/' . $fileName;
               $result =  Storage::disk('public')->put($destinationPath, file_get_contents($temporaryFilePath));

                // Optionally, you can delete the temporary file after saving it permanently
                // unlink($temporaryFilePath);     

               if($result){
                        $path =   'policyDocuments/' . $fileName;
                        $policyFile = PoliciesFiles::create([
                            'policy_id' => $policy_id ,
                            'document_name' => $fileName,
                            'document_type' => "DOC",
                            'document_link' => $path,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),  
                        ]);
                    }
            }

            if ($request->text == 'on') {

                $originalName = $policyDeatil->name;
                // Remove spaces from the original name
                $trimfileName = str_replace(' ', '', $originalName);
                // Generate a unique file name
                $fileName = $trimfileName . time() .  '.txt';

                // Remove any editor-specific effects from $policyText
                $policyText = strip_tags($policyText);

                // Save the content permanently using Laravel's Storage facade
                $destinationPath = 'policyDocuments/' . $fileName;
                $result = Storage::disk('public')->put($destinationPath, $policyText);

               if($result){
                    $path =   'policyDocuments/' . $fileName;
                    $policyFile = PoliciesFiles::create([
                        'policy_id' => $policy_id ,
                        'document_name' => $fileName,
                        'document_type' => "TXT",
                        'document_link' => $path,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
            return Response()->json(['status'=>200, 'policyFile'=>$policyFile]);
        }

        
    //     if($request->hasfile('add_document')){
    //         foreach($request->file('add_document') as $file)
    //         {
    //         $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    //         $dateString = date('YmdHis');
    //         $name = $dateString . '_' . $fileName . '.' . $file->extension();
    //         $file->move(public_path('assets/img/projectAssets'), $name);  
    //         $path='projectAssets/'.$name;
    //             $documents = ProjectFiles::create([
    //             'document' => $path,
    //             'project_id'=> $projects->id,
    //             ]); 
    //         }
    //    }

        $request->session()->flash('message','Policy added successfully.');
    	return Response()->json(['status'=>200, 'policies'=>$policies]);

    }

    public function storeDocument(Request $request)
    {
        // dd($request->add_document);
        $validator = \Validator::make($request->all(),[
            'policy_name' => 'required',
            'add_document.*' => 'file|mimes:pdf|max:5000',
        ],[
            'add_document.*.file' => 'The :attribute must be a file.', 
            'add_document.*.mimes' => 'The :attribute must be a file of type: pdf.',
            'add_document.*.max' => 'The :attribute may not be greater than :max kilobytes.',
            'add_document.*.max.file' => 'The :attribute failed to upload. Maximum file size allowed is :max kilobytes.',

        ]);

        $validator->setAttributeNames([
            'add_document.*' => 'document',
        ]);

        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
            
    	$validate = $validator->valid();
        $policies = Policies::create([
            'name' => $validate['policy_name'],
            'policy_text' => null,
        ]);
        if($policies){
            $policy_id = $policies->id;

            if($request->hasfile('add_document')){
                foreach($request->file('add_document') as $file)
                {
                $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $dateString = date('YmdHis');
                $fileExtension =  $file->extension();
                $name = $dateString . '_' . $fileName . '.' . $fileExtension;
                // $file->move(public_path('assets/img/projectAssets'), $name); 
                Storage::disk('public')->put('policyDocuments/' . $name, file_get_contents($file)); 
                $path='policyDocuments/'.$name;
                    $documents = PoliciesFiles::create([
                    'policy_id' => $policy_id ,
                    'document_name' => $name,
                    'document_link' => $path,
                    'document_type' => strtoupper($fileExtension),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),  
                    ]);  
                    // if($documents){
                    //      // Get the full path to the PDF file
                    //     $fullPath = Storage::path('public/' . $path);
                    //     // Perform PDF to text conversion
                    //         $text = Pdf::getText($fullPath);
                    //     // dd($text);
                    //     $policies = Policies::where('id', $policy_id)  
                    //     ->update([
                    //         'policy_text' => $text,
                    //         'updated_at' => date('Y-m-d H:i:s'),  
                    //     ]);
                    // } 
                }
                    
            }
        }
       

    $request->session()->flash('message','Policy Document Added successfully.');
    return Response()->json(['status'=>200, 'policies'=>$policies]);

    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Policies  $policies
     * @return \Illuminate\Http\Response
     */
    public function show(Policies $policies)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Policies  $policies
     * @return \Illuminate\Http\Response
     */
    public function edit(Policies $policies ,$policyId)
    {
           $policies = Policies::where(['id' => $policyId])->first();
           $PoliciesDocuments= PoliciesFiles::orderBy('id','desc')->where(['policy_id' => $policyId])->get();
   
           return view('policies.edit',compact('policies','PoliciesDocuments'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Policies  $policies
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $policyId)
    {
        $validator = \Validator::make($request->all(),[
            'edit_name' => 'required',
            'edit_policy_text' => 'required',
            'edit_document.*' => 'file|mimes:jpg,jpeg,png,doc,docx,xls,xlsx,pdf|max:5000',
            ],[
                'edit_document.*.file' => 'The :attribute must be a file.', 
                'edit_document.*.mimes' => 'The :attribute must be a file of type:pdf.',
                'edit_document.*.max' => 'The :attribute may not be greater than :max kilobytes.',
                'edit_document.*.max.file' => 'The :attribute failed to upload. Maximum file size allowed is :max kilobytes.',

            ]);

            $validator->setAttributeNames([
                'edit_document.*' => 'document',
            ]);

            if ($validator->fails())
            {
                return response()->json(['errors'=>$validator->errors()->all()]);
            }
    		$validate = $validator->valid();
            $policyData = Policies::find($policyId);
            $policies = Policies::where('id', $policyId)  
            ->update([
                'name' => $validate['edit_name'],
                'policy_text' => $validate['edit_policy_text'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),  
            ]);
            if ($policies && $policyData->policy_text != $validate['edit_policy_text'] ) {
                $policyDeatil = Policies::find($policyId);
                $policyText = $policyDeatil->policy_text;
                $policyFiles = PoliciesFiles::where('policy_id', $policyId)->where('deleted_at', null)->get();
                // dd($policyFiles);
                if($request->pdf == 'on'){
                    // Initialize the PDF generator
                        $dompdf = new Dompdf();
            
                    // Load the editor text as HTML content
                    $dompdf->loadHtml($policyText);
            
                    // (Optional) Set paper size and orientation (e.g., A4 portrait)
                    $dompdf->setPaper('A4', 'portrait');
            
                    // // Render the PDF
                    $dompdf->render();
                    $originalName = $policyDeatil->name;
                    // Remove spaces from the original name
                    $trimfileName = str_replace(' ', '', $originalName);
                    // Generate a unique file name
                    $fileName = $trimfileName . time() . '.pdf';
                    $path =   'policyDocuments/' . $fileName;
                    // Save the PDF to the 'public' disk under the 'policyDocuments' directory
                    $result = Storage::disk('public')->put($path, $dompdf->output());
                    if($result){
                        foreach ($policyFiles as $pFile) {

                            $file_type = strtolower($pFile->document_type);
                            if($file_type == 'pdf'){
                                $policy_file_path = $pFile->document_link;
                                if (Storage::disk('public')->exists($policy_file_path)) {
                                 Storage::disk('public')->delete($policy_file_path);
                                      
                                $policyFile = PoliciesFiles::where('id',$pFile->id)->update([
                                    'document_name' => $fileName,
                                    'document_type' => "PDF",
                                    'document_link' => $path,
                                    'updated_at' => date('Y-m-d H:i:s'),  
                                ]);
                                    
                                } else {
                                    
                                    return "PDF Document $policy_file_path not found.";
                                }
                              
                            }
                            
                        }
                    }
                    
                }
                
                if($request->word == 'on'){
                      
                // Initialize the PHPWord object
                $phpWord = new PhpWord();

                // Add a new section to the document
                $section = $phpWord->addSection();

                // Replace line breaks in $policyText with CRLF
                $policyText = str_replace(PHP_EOL, "\r\n", $policyText);

                // Add the editor text content as a paragraph
                $section->addText($policyText);

                $originalName = $policyDeatil->name;
                // Remove spaces from the original name
                $trimfileName = str_replace(' ', '', $originalName);
                // Generate a unique file name
                $fileName = $trimfileName . time() .  '.docx';

                // Save the Word document to a temporary file
                $writer = IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save(storage_path('app/' . $fileName));

                // // Save the Word document to a temporary file
                // $temporaryFilePath = storage_path('app/' . $fileName);
                // $writer->save($temporaryFilePath);

                // Now, save the Word document permanently using Laravel's Storage facade
                $destinationPath = 'policyDocuments/' . $fileName;
               $result =  Storage::disk('public')->put($destinationPath, file_get_contents($temporaryFilePath));

                // Optionally, you can delete the temporary file after saving it permanently
                // unlink($temporaryFilePath);     

               if($result){
                        $path =   'policyDocuments/' . $fileName;
                        $policyFile = PoliciesFiles::create([
                            'policy_id' => $policyId,
                            'document_name' => $fileName,
                            'document_type' => "DOC",
                            'document_link' => $path,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),  
                        ]);
                    }
            }

            if ($request->text == 'on') {

                $originalName = $policyDeatil->name;
                // Remove spaces from the original name
                $trimfileName = str_replace(' ', '', $originalName);
                // Generate a unique file name
                $fileName = $trimfileName . time() .  '.txt';

                // Remove any editor-specific effects from $policyText
                $policyText = strip_tags($policyText);

                // Save the content permanently using Laravel's Storage facade
                $destinationPath = 'policyDocuments/' . $fileName;
                $result = Storage::disk('public')->put($destinationPath, $policyText);

               if($result){
                    $path =   'policyDocuments/' . $fileName;
                    $policyFile = PoliciesFiles::create([
                        'policy_id' => $policyId ,
                        'document_name' => $fileName,
                        'document_type' => "TXT",
                        'document_link' => $path,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),  
                    ]);
                }
            }
            // return Response()->json(['status'=>200]);
            }
        //     if($request->hasfile('edit_document')){
        //         foreach($request->file('edit_document') as $file)
        //         {
        //         $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        //         $dateString = date('YmdHis');
        //         $name = $dateString . '_' . $fileName . '.' . $file->extension();
        //         $file->move(public_path('assets/img/projectAssets'), $name);  
        //         $path='projectAssets/'.$name;
        //             $documents = ProjectFiles::create([
        //             'document' => $path,
        //             'project_id'=> $projectId,
        //             ]); 
        //         }
               
        //    }
        $request->session()->flash('message','Policy updated successfully.');
        return redirect()->back()->with('policies', $policies);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Policies  $policies
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $policies = Policies::where('id',$request->id)->delete();
        $policyFiles = PoliciesFiles::where('policy_id',$request->id)->get();
        foreach ($policyFiles as $policyFile) {
            $policy_file_path = $policyFile->document_link;
          // Ensure the file exists before attempting to delete it
            if (Storage::disk('public')->exists($policy_file_path)) {
                Storage::disk('public')->delete($policy_file_path);
                $msg = "Document $policy_file_path has been deleted.";
            } else {
                $msg = "Document $policy_file_path not found.";
            }
            $policyFile->delete();
        }
        
        $request->session()->flash('error','Policy deleted successfully.');
       return Response()->json(['status'=>200]);
    }

    public function showPolicy($policyId)
    {
        $policies = Policies::with('PolicyDocuments')->find($policyId); 
        // $projectAssigns= ProjectAssigns::join('users', 'project_assigns.user_id', '=', 'users.id')->where('project_id',$projectId)->orderBy('id','desc')->get(['project_assigns.*','users.first_name', 'users.profile_picture']);
        $PoliciesDocuments= PoliciesFiles::orderBy('id','desc')->where(['policy_id' => $policyId])->get();
        return view('policies.show',compact('policies','PoliciesDocuments'));
    }
}
