<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $messages['subject'] ?? 'Client Access Request' }}</title>
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
        <p><strong>{{ $messages['greeting-text'] ?? 'Hello!' }}</strong></p>
        <p>{{ $messages['title'] ?? '' }}</p>
        <p>
            <strong>Name:</strong> {{ $user->first_name }} {{ $user->last_name }}<br>
            <strong>Email:</strong> {{ $user->email }}
        </p>
        <p>
            {{ $messages['body-text'] ?? '' }}
        </p>
        <p>
            <a href="{{ url($messages['url'] ?? '/') }}" class="button">
                {{ $messages['url-title'] ?? 'View' }}
            </a>
        </p>
         <p>Regards,<br>PMS</p>
            <hr>
            <p class="footer">
                If you're having trouble clicking the "{{ $messages['url-title'] ?? 'View' }}" button, copy and paste the URL below into your web browser: <a href="{{ url($messages['url'] ?? '/') }}">{{ url($messages['url'] ?? '/') }}</a>
            </p>
    </div>
</body>
</html>
