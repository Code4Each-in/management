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
            'subject'       => 'required|string|max:255',
            'message'       => 'required|string',
            'emails'        => 'required|array',
            'emails.*'      => 'email',
            'attachments'   => 'nullable|array',
            'attachments.*' => 'file', // This line expects each to be an actual uploaded file
        ]);

        $subject = $request->input('subject');
        $message = $request->input('message');
        $attachments = $request->file('attachments', []);
        foreach ($request->emails as $email) {
            Mail::send('Email.employee_mail', [
                'mailSubject' => $subject,
                'emailBody' => $message,
            ], function ($mail) use ($email, $subject, $attachments) {
                $mail->to($email)->subject($subject);
                foreach ($attachments as $file) {
                    $mail->attach($file->getRealPath(), [
                        'as'   => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                    ]);
                }
            });
        }

        return back()->with('success', 'Emails sent successfully!');
    }
}
