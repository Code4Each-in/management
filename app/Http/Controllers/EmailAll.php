<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Users;
use Illuminate\Support\Facades\Mail;

class EmailAll extends Controller
{
    /**
     * Show the form to send emails to employees.
     */
    public function index()
{
    $employees = Users::where('status', 1)->get();

    return view('Email.hrEmail', compact('employees'));
}


    /**
     * Send emails to selected employees.
     */
    public function sendMail(Request $request)
    {
        $request->validate([
            'subject'   => 'required|string|max:255',
            'message'   => 'required|string',
            'footer'    => 'nullable|string',
            'emails'    => 'required|array',
            'emails.*'  => 'email'
        ]);

        $subject = $request->input('subject');
        $message = $request->input('message');  // No need to strip tags
        $footer = $request->input('footer');    // No need to strip tags

        $finalMessage = $message . "<br><br>" . $footer;  // Ensure HTML line breaks are preserved

        foreach ($request->emails as $email) {
            Mail::send([], [], function ($mail) use ($email, $subject, $finalMessage) {
                $mail->to($email)
                    ->subject($subject)
                    ->setBody($finalMessage, 'text/html');  // Specify HTML format
            });
        }

        return back()->with('success', 'Emails sent successfully!');
    }


}
