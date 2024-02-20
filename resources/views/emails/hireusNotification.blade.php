<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Client Inquiry</title>
    <style>
        /* Reset styles */
        body, h1, p {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        .details {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .details p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>New Client Inquiry</h1>
        <div class="details">
            <p><strong>Name:</strong> {{$data['name']}}</p>
            <p><strong>Email:</strong>{{$data['email']}}</p>
            <p><strong>Tech Stack:</strong>{{$data['skill']}}</p>
            <p><strong>Message:</strong> {{$data['message']}}</p>
        </div>

        <p>The above-named client has shown interest in hiring us for the specified tech stack. Please follow up accordingly.</p>
        <p>Best regards,<br><strong>Admin Code4Each</strong></p>
    </div>
</body>
</html>
