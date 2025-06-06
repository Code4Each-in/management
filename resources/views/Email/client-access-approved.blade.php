<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Client Access Approved</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 40px;">
    <div style="max-width: 600px; margin: auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <h2 style="color: #4154F1;">Hello <strong style="color: #000;">{{ $user->first_name }}</strong>,</h2>
        <p style="font-size: 16px; color: #333;">
            Your request for client access has been <strong>approved</strong>. You can now log in and access the platform.
        </p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="https://hr.code4each.com/" style="background-color: #FF7777; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                Login Now
            </a>
        </p>
        <p style="font-size: 14px; color: #888;">Regards,<br>PMS</p>
    </div>
</body>
</html>
