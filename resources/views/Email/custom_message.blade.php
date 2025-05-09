<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $subject }}</title>
</head>
<body>
    <h2>{{ $title }}</h2>
    <p>{!! nl2br(e($body)) !!}</p>

    <br><br>
    <p>Regards,<br>Code4Each</p>
</body>
</html>
