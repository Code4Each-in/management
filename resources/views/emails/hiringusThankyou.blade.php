
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Contacting Us!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333333;
        }
        p {
            color: #333333;
            line-height: 1.6;
        }
        .signature {
            text-align: left;
            margin-top: 20px;
            font-style: italic;
            color: #333333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Thank You for Contacting Us!</h1>
        <p>Dear <strong>{{$data['name']}}</strong>,</p>
        <p>We are incredibly grateful for your interest in hiring us for <strong>{{$data['skill']}}</strong> . Your inquiry means a lot to us, and we're excited about the opportunity to collaborate with you!</p>
        <p>Thank you once again for considering us. We will contact you soon!</p>
        <div class="signature">
            <p>Best regards,</p>
            <p><strong>Code4Each</strong></p>
        </div>
    </div>
</body>
</html>
