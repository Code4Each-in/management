<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $mailSubject }}</title>

  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font Awesome for social icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    body {
      background-color: #f4f4f4;
      font-family: 'Segoe UI', sans-serif;
    }

    .email-container {
      max-width: 650px;
      margin: 30px auto;
      background: #ffffff;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
      overflow: hidden;
    }

    .email-header {
      background-color: #ffc107;
      color: black;
      padding: 25px;
      text-align: center;
    }

    .email-header h3 {
      font-size: 25px;
      margin: 0;
    }

    .email-header img {
      max-width: 150px;
      margin-bottom: 10px;
    }

    .email-body {
      padding: 30px;
    }

    .email-footer {
      background-color: #f8f9fa;
      text-align: center;
      padding: 20px;
    }

    .social-icons a {
      margin: 0 10px;
      color: #000;
      text-decoration: none;
      font-size: 20px;
      transition: color 0.3s, transform 0.3s;
    }

    .social-icons a:hover {
      color: #ffc107;
      transform: scale(1.2);
    }

    @media (max-width: 576px) {
      .email-body {
        padding: 20px;
      }
    }
  </style>
</head>
<body>

  <div class="email-container">
    <!-- Header -->
    <div class="email-header">
    <img src="https://hr.code4each.com/assets/img/code4each_logo.png" alt="Code4Each Logo" style="height:40px !important; margin-top:5px !important;">
      <h3>{{ $mailSubject }}</h3>
    </div>

    <!-- Body -->
    <div class="email-body">
    {!! $emailBody !!}
    </div>

    <!-- Footer -->
    <div class="email-footer">
      <p>Connect with us:</p>
      <div class="social-icons">
        <a href="https://in.linkedin.com/company/code-4-each"><i class="fab fa-linkedin-in" title="LinkedIn"></i></a>
        <a href="https://www.instagram.com/code.4each/"><i class="fab fa-instagram" title="Instagram"></i></a>
      </div>
      <p class="mt-3 text-muted" style="font-size: 13px;">© {{ date('Y') }} Code4Each. All rights reserved.</p>
    </div>
  </div>

</body>
</html>
