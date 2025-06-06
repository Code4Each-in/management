<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Access Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            background-color: #f4f4f4;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333333;
        }
        strong {
            color: #333333;
        }
        p {
            color: #555555;
        }
        .button {
            display: inline-block;
            background-color:#555555;
            color: #ffffff !important;
            text-decoration: none;
            padding: 10px 20px;
            margin-top: 20px;
            border-radius: 4px;
            text-align: center !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <p><strong>New Client Access Request</strong></p>
        <p>A client has requested access to the platform. Details are below:</p>
        <p>
            <strong>Name:</strong> <span>{{ $user->first_name }} {{ $user->last_name }}</span><br>
            <strong>Email:</strong> <span>{{ $user->email }}</span>
        </p>
        <p>
            Please log in to the admin panel to approve or reject the request.
        </p>
        <p>
            <a href="https://pms.code4each.com/admin/client-access-requests" class="button">
                View Requests
            </a>
        </p>
         <p>Regards,<br>PMS</p>
            <hr>
            <p class="footer">
                If you're having trouble clicking the "View Requests" button, copy and paste the URL below into your web browser: <a href="https://pms.code4each.com/admin/client-access-requests">https://pms.code4each.com/admin/client-access-requests</a>
            </p>
    </div>
</body>
</html>
