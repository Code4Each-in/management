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
        $employees = Users::where('status', 1)->whereNull('client_id')->get();

        return view('Email.hrEmail', compact('employees'));
    }



    /**
     * Send emails to selected employees.
     */
    public function sendMail(Request $request)
    {
        $request->validate([
            'subject'       => 'required|string|max:255',
            'message'       => [
                'required',
                function ($attribute, $value, $fail) {
                    $stripped = trim(strip_tags($value));
                    if ($stripped === '') {
                        $fail('The message field cannot be empty.');
                    }
                }
            ],
            'emails'        => 'required|array|min:1',
            'emails.*'      => 'required|email',
            'attachments'   => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,zip|max:2048', // Optional: file validation
        ], [
            'emails.required'    => 'Please select at least one employee to send email.',
            'emails.*.email'     => 'One or more email addresses are invalid.',
            'message.required'   => 'The message field is required.',
            'attachments.*.mimes' => 'Attachments must be of type jpg, jpeg, png, pdf, doc, docx, or zip.',
            'attachments.*.max'  => 'Each attachment must not exceed 2MB.',
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
