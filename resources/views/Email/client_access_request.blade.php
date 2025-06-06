<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Access Request</title>
</head>
<body>
    <h2>New Client Access Request</h2>
    <p>A client has requested access to the platform.</p>

    <p><strong>Name:</strong> {{ $user->first_name }} {{ $user->last_name }}</p>
    <p><strong>Email:</strong> {{ $user->email }}</p>

    <p>Please log in to the admin panel to approve or reject the request.</p>
</body>
</html>
