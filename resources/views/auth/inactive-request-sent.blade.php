<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Request Sent</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-white">

    <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
        <div class="text-center p-5 shadow" style="border: 1px solid #eee; background-color: #fff;">
            <h4 class="text-primary mb-3" style="color: #4154F1;">Request Sent</h4>
            <p class="text-muted mb-4">Your access request has been submitted successfully.<br>Please wait for admin approval.</p>
            <a href="{{ route('login') }}" class="btn" style="background-color: #FF6767; color: #fff;">Back to Login</a>
        </div>
    </div>

</body>
</html>
