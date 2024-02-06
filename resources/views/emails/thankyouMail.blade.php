<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Arial', sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 600px;
      margin: 20px auto;
      padding: 20px;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    h2 {
      color: #333;
    }

    p {
      color: #555;
    }

    .applicant-info {
      margin-top: 15px;
      padding: 15px;
      background-color: #f9f9f9;
      border-radius: 8px;
    }

    .footer {
      margin-top: 20px;
      color: #777;
      font-size: 12px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Thank You for Applying!</h2>
    <p>Hello {{$data['applicant']['name']}},</p>
    <p>We appreciate your recent application for the position of <strong>{{$data['title']}}</strong> at our company. Your interest in joining our team is valued.</p>

    <div class="applicant-info">
      <p><strong>Name:</strong> {{$data['applicant']['name']}}</p>
      <p><strong>Email:</strong> {{$data['applicant']['email']}}</p>
      <p><strong>Phone:</strong> {{$data['applicant']['phone']}}</p>
    </div>

    <p>If you have any further questions or need additional information, feel free to reach out to us. We will review your application carefully and get back to you as soon as possible.</p>

    <p>Thank you once again for considering a career with us. We wish you the best in your job search!</p>

    <div class="footer">
      <p>Best Regards,<br><strong>Code4each</strong></p>
    </div>
  </div>
</body>
</html>
