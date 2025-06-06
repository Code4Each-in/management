<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Inactive Client</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        body {
            background: #f8f9fa; /* Light grey background */
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card-center {
            background: #fff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(65, 84, 241, 0.15);
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        h4 {
            font-weight: 700;
            color: #FF6767; /* red accent */
            margin-bottom: 0.75rem;
        }
        p {
            color: #555;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        .btn-primary {
            background: #4154F1; /* blue */
            border: none;
            font-size: 1.1rem;
            padding: 12px 30px;
            border-radius: 8px;
            box-shadow: 0 6px 12px rgba(65, 84, 241, 0.35);
            transition: background 0.3s ease, transform 0.2s ease;
            width: 100%;
            max-width: 280px;
            margin: 0 auto;
        }
        .btn-primary:hover, .btn-primary:focus {
            background: #2f3fcf;
            box-shadow: 0 8px 20px rgba(41, 55, 207, 0.5);
            transform: translateY(-3px);
        }
        .icon-circle {
            background: #FF6767;
            color: white;
            width: 60px;
            height: 60px;
            line-height: 60px;
            border-radius: 50%;
            font-size: 2.5rem;
            margin: 0 auto 25px;
            box-shadow: 0 6px 18px rgba(255, 103, 103, 0.35);
        }
    </style>
</head>
<body>

    <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
        <div class="card-center">

            <div class="icon-circle">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>

            @if(session('request_sent'))
                <h4>Thank you!</h4>
                <p>Your request has been sent successfully.<br>Please wait for approval from the admin team.</p>
            @else
                <h4>Your account is inactive</h4>
                <p>Click the button below to request access from the admin team.</p>
                <form method="POST" action="{{ route('client.request-access') }}">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                    <button type="submit" class="btn btn-primary">Request Access</button>
                </form>
            @endif

        </div>
    </div>

</body>
</html>
