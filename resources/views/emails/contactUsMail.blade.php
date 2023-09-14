<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Contact Us</title>
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
        <h1>Contact Us</h1>
        <p>Dear Team,</p>
        <p>I hope this message finds you well. We wanted to inform you that we have received a new inquiry through our Contact Us page on the website. Here are the details:</p>
        
        <table>
            <tr>
                <td><strong>Name:</strong></td>
                <td>{{ ucfirst($data['name']) }}</td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td>{{$data['email'] }}</td>
            </tr>
            <tr>
                <td><strong>Phone:</strong></td>
                <td>{{ $data['phone'] }}</td>
            </tr>
            <tr>
                <td><strong>Message:</strong></td>
                <td>{{ $data['message'] }}</td>
                
            </tr>
        </table>
        <br>
        <p>Visit HR Management: <a href="{{ url('/') }}" target="_blank">Click Here </a></p>
        
    </div>
</body>
</html>