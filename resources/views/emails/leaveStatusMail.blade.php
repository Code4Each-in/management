<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Leave Request {{ ucfirst($data['leave_status']) }} </title>
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
        <h1>Leave Request {{ ucfirst($data['leave_status']) }}</h1>
        <p>Dear <strong>{{ ucfirst($data['first_name']).' '. ucfirst($data['last_name'])}}</strong></p>
        <p>I hope this email finds you well. I have reviewed your leave application, and I am writing to inform you of the decision regarding your request.</p>

        @if ($data['leave_status'] === 'approved')

            <p>I am pleased to inform you that your leave application has been approved. You are granted the requested leave from <strong>{{ date('d-m-Y', strtotime($data['from'])) }}</strong> to <strong>{{ date('d-m-Y', strtotime($data['to']))}}</strong> @if ($data['half_day'] != null), during the <strong>{{$data['half_day']}}</strong> of the day @endif. Enjoy your time off, and please make sure to complete any necessary handover tasks before your departure.
            </p>
            
            @elseif ($data['leave_status'] === 'declined')

            <p>I regret to inform you that your leave application has been denied. We are unable to approve your requested leave from <strong>{{ date('d-m-Y', strtotime($data['from'])) }}</strong> to <strong>{{ date('d-m-Y', strtotime($data['to']))}}</strong> @if ($data['half_day'] != null), during the <strong>{{$data['half_day']}}</strong> of the day @endif. Please consider rescheduling your leave to a more suitable time. If you have any questions or concerns, feel free to discuss them with your supervisor or the HR department.
            </p>

        @endif
        
        <p>Thank you for your understanding in this matter.</p>

        <p>Best regards,</p>
        <p>Team HR</p>
        <p>Code4Each</p>
        <p>Visit HR Management: <a href="{{ url('/') }}" target="_blank">Click Here </a></p>

        
        
    </div>
</body>
</html>