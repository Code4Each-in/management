<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Leave Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333333;
        }
        strong{
            color: #333333;
        }
        p {
            color: #555555;
        }
        .button {
            display: inline-block;
            background-color: #4caf50;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            margin-top: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Leave Request</h1>
        <p>Dear HR Team,</p>
        <p>I hope this email finds you well. I am writing to formally request a leave of absence from {{$data['from']}} to {{$data['to']}}.</p>
        <p>Please review the details below:</p>
        
        <table>
            <tr>
                <td><strong>Employee Name:</strong></td>
                <td>{{$data['first_name'].' '. $data['last_name']}}</td>
            </tr>
            <tr>
                <td><strong>Leave Type:</strong></td>
                <td>{{$data['type'] }}</td>
            </tr>
            <tr>
                <td><strong>Start Date:</strong></td>
                <td>{{$data['from'] }}</td>
            </tr>
            <tr>
                <td><strong>End Date:</strong></td>
                <td>{{$data['to']}}</td>
            </tr>
            <tr>
                <td><strong>Reason:</strong></td>
                <td>{{ $data['notes'] }}</td>
            </tr>
        </table>
        <br>
        <p>Please take appropriate action regarding this leave request.</p>
        
        <p>Thank you.</p>
        <p>Best regards,</p>
        <p>{{$data['first_name'].' '. $data['last_name']}}</p>

        <p>Visit HR Management: <a href="#" target="_blank">Click Here </a></p>
        
    </div>
</body>
</html>