<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Access Request</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 40px;">
    <div style="max-width: 600px; margin: auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <h2 style="color: #4154F1;">New Client Access Request</h2>
        
        <p style="font-size: 16px; color: #333;">
            A client has requested access to the platform. Details are below:
        </p>

        <p style="font-size: 16px; color: #000;">
            <strong>Name:</strong> <span style="color: #000; font-weight: bold;">{{ $user->first_name }} {{ $user->last_name }}</span><br>
            <strong>Email:</strong> <span style="color: #000;">{{ $user->email }}</span>
        </p>

        <p style="font-size: 16px; color: #333;">
            Please log in to the admin panel to approve or reject the request.
        </p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="https://hr.code4each.com/admin/client-access-requests" style="background-color: #FF7777; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                View Requests
            </a>
        </p>

        <p style="font-size: 14px; color: #888;">Thank you,<br>The System</p>
    </div>
</body>
</html>
