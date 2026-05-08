<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $messages['subject'] ?? 'Feedback Received' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family:Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4; padding:40px 16px;">
    <tr>
        <td align="center">
            <table width="580" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; border:1px solid #e8e8e8;">

                <!-- LOGO -->
                <tr>
                    <td style="padding:28px 40px; border-bottom:1px solid #f0f0f0;">
                        <img src="https://hr.code4each.com/assets/img/code4each_logo.png" 
                             alt="Code4Each"
                             style="height:32px; width:auto; display:block;">
                    </td>
                </tr>

                <!-- BODY -->
                <tr>
                    <td style="padding:36px 40px;">

                        <p style="margin:0 0 6px; font-size:13px; color:#4F46E5; font-weight:bold; text-transform:uppercase; letter-spacing:0.6px;">
                            New Feedback Received
                        </p>

                        <h2 style="margin:0 0 16px; font-size:22px; color:#1a1a1a; font-weight:600; line-height:1.3;">
                            {{ $messages['title'] ?? '' }}
                        </h2>

                        <!-- TICKET INFO -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9f9f9; border-radius:8px; border:1px solid #f0f0f0; margin-bottom:24px;">
                            <tr>
                                <td style="padding:18px 20px;">
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="font-size:13px; color:#888; padding:5px 0;">Ticket</td>
                                            <td style="font-size:13px; color:#1a1a1a; font-weight:600; text-align:right; padding:5px 0;">
                                                #{{ $messages['ticket_id'] ?? '' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px; color:#888; padding:5px 0;">Rating</td>
                                            <td style="font-size:13px; font-weight:600; text-align:right; padding:5px 0; color:#f59e0b;">
                                                @for($i = 1; $i <= 5; $i++)
                                                    {{ $i <= ($messages['rating'] ?? 0) ? '★' : '☆' }}
                                                @endfor
                                                ({{ $messages['rating'] ?? 0 }}/5)
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <!-- COMMENT BOX -->
                        <p style="margin:0 0 8px; font-size:13px; color:#888; text-transform:uppercase; letter-spacing:0.5px;">Client Comment</p>
                        <div style="background:#f6f6f6; border-left:4px solid #4F46E5; border-radius:0 8px 8px 0; padding:16px 20px; margin-bottom:28px;">
                            <p style="margin:0; font-size:14px; color:#333; line-height:1.7;">
                                {{ $messages['body-text'] ?? '' }}
                            </p>
                        </div>

                        <!-- CTA -->
                        <a href="{{ config('app.url') . ($messages['url'] ?? '#') }}"
                           style="display:inline-block; background:#4F46E5; color:#ffffff;
                                  padding:13px 30px; text-decoration:none; border-radius:7px;
                                  font-size:15px; font-weight:bold;">
                            {{ $messages['url-title'] ?? 'View Ticket' }}
                        </a>

                    </td>
                </tr>

                <!-- FOOTER -->
                <tr>
                    <td style="padding:20px 40px; border-top:1px solid #f0f0f0; background:#fafafa;">
                        <p style="margin:0; font-size:13px; color:#999;">
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