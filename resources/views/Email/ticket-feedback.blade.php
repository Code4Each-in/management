<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $messages['subject'] ?? 'Notification' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f5f5; font-family:Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
    <tr>
        <td align="center">
            <table width="580" cellpadding="0" cellspacing="0"
                   style="background:#ffffff; border-radius:6px; overflow:hidden; border:1px solid #e0e0e0;">

                <!-- LOGO HEADER -->
                <tr>
                    <td style="background:#ffffff; padding:24px 40px; border-bottom:3px solid #4F46E5;">
                        <img src="https://hr.code4each.com/assets/img/code4each_logo.png"
                             alt="Code4Each"
                             style="height:30px; width:auto; display:block;">
                    </td>
                </tr>

                <!-- BODY -->
                <tr>
                    <td style="padding:40px 40px 0;">

                        <!-- GREETING -->
                        <p style="margin:0 0 20px; font-size:15px; color:#374151;">
                            Hi <strong>{{ $messages['client_name'] ?? 'there' }}</strong>,
                        </p>

                        <!-- BODY TEXT -->
                        <p style="margin:0 0 14px; font-size:15px; color:#374151; line-height:1.7;">
                            {{ $messages['body-text'] ?? '' }}
                        </p>

                        <p style="margin:0 0 32px; font-size:15px; color:#374151; line-height:1.7;">
                            {{ $messages['action-message'] ?? '' }}
                        </p>

                        <!-- PRIMARY BUTTON -->
                        <table cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
                            <tr>
                                <td>
                                    <a href="{{ config('app.url') . ($messages['url'] ?? '#') }}"
                                       style="display:inline-block; background:#4F46E5; color:#ffffff;
                                              padding:12px 26px; text-decoration:none; border-radius:6px;
                                              font-size:14px; font-weight:600;">
                                        {{ $messages['url-title'] ?? 'Submit Feedback' }}
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <!-- SECONDARY BUTTON -->
                        <table cellpadding="0" cellspacing="0" style="margin-bottom:36px;">
                            <tr>
                                <td>
                                    <a href="{{ config('app.url') . '/tickets/create' }}"
                                       style="display:inline-block; background:#ffffff; color:#4F46E5;
                                              padding:11px 26px; text-decoration:none; border-radius:6px;
                                              font-size:14px; font-weight:600; border:1px solid #4F46E5;">
                                        Create New Ticket
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
                            Need help? Just reply to this email. &nbsp;&bull;&nbsp;
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