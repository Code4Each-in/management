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

    .resume-link {
      display: block;
      margin-top: 15px;
      color: #3498db;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>New Job Application</h2>
    <p>Hello HR Team,</p>
    <p>We have received a new job application for the position of {{$data['title']}}. Below are the details of the applicant:</p>

    <div class="applicant-info">
      <p><strong>Name:</strong> {{$data['applicant']['name']}}</p>
      <p><strong>Email:</strong> {{$data['applicant']['email']}}</p>
      <p><strong>Phone:</strong> {{$data['applicant']['phone']}}</p>
    </div>

    <p>You can review the applicant's resume by clicking the link below:</p>
    <a href="https://hr.code4each.com/assets/docs/{{$data['applicant']['resume']}}" class="resume-link" target="_blank">View Resume</a>

    <p>Please take the necessary steps to review and process the application. Thank you!</p>
  </div>
</body>
</html>

