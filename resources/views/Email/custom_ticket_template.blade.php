<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $messages['subject'] }}</title>
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
        <p><strong>{{ $messages['greeting-text'] ?? '' }}</strong></p>
        <p>A new comment has been added to Ticket # <strong>{{ $messages['body-text'] ?? '' }}</strong>.<br> Please review the comment and provide a response if necessary.</p>
        @if(!empty($messages['url']))
            <p>
                <a href="{{ url($messages['url']) }}" class="button">
                    {{ $messages['url-title'] ?? 'Action' }}
                </a>
            </p>
        @endif
         <p>Regards,<br>PMS</p>
        @if(!empty($messages['url']))
            <hr>
            <p class="footer">
                If you're having trouble clicking the "{{ $messages['url-title'] ?? 'Action' }}" button, copy and paste the URL below into your web browser: <a href="{{ url($messages['url']) }}">{{ url($messages['url']) }}</a>
            </p>
        @endif
    </div>
</body>
</html>
