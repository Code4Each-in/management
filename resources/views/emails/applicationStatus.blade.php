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
      max-width: 700px;
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

    .selected {
      color: #4CAF50;
    }

    .rejected {
      color: #FF5252;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Application Status</h2>
    <p>Dear Applicant,</p>
    @if ($data['application_status'] === 'rejected')
      <p class="">We want to express our sincere gratitude for your application for the position of <strong>{{$data['title']}}</strong>. After careful consideration, we regret to inform you that you have not been selected for the current opening.</p>
      <p>Thank you for your interest in our organization. We wish you the very best in your career journey and look forward to the possibility of connecting with you in the future.</p>
    @endif
    @if ($data['application_status'] === 'selected')
      <p class="">Congratulations! You've been selected  for the position of
        <strong>{{$data['title']}}</strong>. </p>
    @endif


    <p>If you have any feedback or questions regarding the application process, please feel free to reach out to our HR department at <strong>hr@code4each.com</strong>.</p>
</div>
</body>
</html>
