<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reminder Notification</title>
</head>

<body style="margin:0; padding:0; background-color:#f5f5f5; font-family:Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
    <tr>
        <td align="center">

            <table width="580" cellpadding="0" cellspacing="0"
                   style="background:#ffffff; border-radius:6px; overflow:hidden; border:1px solid #e0e0e0;">

                <!-- HEADER -->
                <tr>
                    <td style="background:#ffffff; padding:24px 40px; border-bottom:3px solid #4F46E5;">
                        <img src="https://hr.code4each.com/assets/img/code4each_logo.png"
                             alt="Logo"
                             style="height:30px; width:auto; display:block;">
                    </td>
                </tr>

                <!-- BODY -->
                <tr>
                    <td style="padding:40px 40px 0;">

                        <!-- GREETING -->
                        <p style="margin:0 0 20px; font-size:15px; color:#374151;">
                            Hi <strong>{{ $user->name ?? 'there' }}</strong>,
                        </p>

                        <!-- MAIN MESSAGE -->
                        <p style="margin:0 0 14px; font-size:15px; color:#374151; line-height:1.7;">
                            This is a reminder for your scheduled task:
                        </p>

                        <!-- REMINDER CARD -->
                        <div style="background:#f9fafb; border:1px solid #e5e7eb; padding:16px; border-radius:6px; margin-bottom:24px;">

                            <p style="margin:0 0 8px; font-size:14px; color:#111827;">
                                <strong>Description:</strong> {{ $reminder->description }}
                            </p>

                            <p style="margin:0 0 8px; font-size:14px; color:#111827;">
                                <strong>Type:</strong> {{ ucfirst($reminder->type) }}
                            </p>

                            <p style="margin:0; font-size:14px; color:#111827;">
                                <strong>Reminder Date:</strong>
                                {{ \Carbon\Carbon::parse($reminder->reminder_date)->format('d M Y') }}
                            </p>

                        </div>

                        <!-- BUTTON (optional action) -->
                        <table cellpadding="0" cellspacing="0" style="margin-bottom:36px;">
                            <tr>
                                <td>
                                    <a href="{{ config('app.url') }}"
                                       style="display:inline-block; background:#4F46E5; color:#ffffff;
                                              padding:12px 26px; text-decoration:none; border-radius:6px;
                                              font-size:14px; font-weight:600;">
                                        View Dashboard
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <!-- DIVIDER -->
                        <hr style="border:none; border-top:1px solid #e5e7eb; margin:0 0 28px;">

                        <!-- SIGN OFF -->
                        <p style="margin:0 0 4px; font-size:14px; color:#6b7280;">Sincerely,</p>
                        <p style="margin:0 0 36px; font-size:15px; color:#111827; font-weight:600;">
                            Code4Each Team
                        </p>

                    </td>
                </tr>

                <!-- FOOTER -->
                <tr>
                    <td style="padding:20px 40px; background:#f9fafb; border-top:1px solid #e5e7eb; text-align:center;">
                        <p style="margin:0; font-size:12px; color:#9ca3af; line-height:1.6;">
                            You are receiving this email because you set a reminder.
                            &copy; {{ date('Y') }} Code4Each Team. All rights reserved.
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
