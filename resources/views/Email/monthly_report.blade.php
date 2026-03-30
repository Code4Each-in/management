<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Status Report</title>
</head>
<body style="margin:0; padding:0; background-color:#eef0f7; font-family: 'Georgia', serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding: 40px 20px;">
<tr>
<td align="center">

<table width="580" cellpadding="0" cellspacing="0">

    <!-- Header -->
    <tr>
        <td style="padding: 0 0 20px 0;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <img src="https://hr.code4each.com/assets/img/code4each_logo.png"
                             alt="Code4Each"
                             style="height: 32px; display: block;">
                    </td>
                    <td align="right" style="font-family: 'Courier New', monospace; font-size: 10px; color: #8a91a8; letter-spacing: 2px; text-transform: uppercase;">
                        Status Report
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Main Card -->
    <tr>
        <td style="background: #ffffff; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 16px rgba(65,84,241,0.07);">

            <!-- Top accent bar -->
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="height: 4px; background: linear-gradient(90deg, #4154f1 0%, #818cf8 60%, #c7d2fe 100%);"></td>
                </tr>
            </table>

            <!-- Body -->
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding: 38px 44px 32px;">

                        <!-- Date line -->
                        <p style="margin: 0 0 28px; font-family: 'Courier New', monospace; font-size: 10px; color: #b0b7c9; letter-spacing: 2px; text-transform: uppercase;">
                            {{ date('F j, Y') }}
                        </p>

                        <!-- Greeting -->
                        <p style="margin: 0 0 6px; font-size: 23px; color: #1a2040; font-weight: normal; letter-spacing: -0.4px;">
                            Hello, <span style="color: #4154f1;">{{ $client_name }}</span>
                        </p>

                        <p style="margin: 0 0 32px; font-size: 14px; color: #6b7280; line-height: 1.75; font-family: Arial, sans-serif;">
                            Here's the latest status breakdown for your project.
                        </p>

                        <!-- Project name block -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                            <tr>
                                <td style="border-left: 3px solid #fe6466; padding: 10px 18px; background: #f8f9ff;">
                                    <p style="margin: 0 0 3px; font-family: 'Courier New', monospace; font-size: 9px; color: #b0b7c9; letter-spacing: 2px; text-transform: uppercase;">Project</p>
                                    <p style="margin: 0; font-size: 16px; color: #297bab; letter-spacing: -0.3px;">{{ $project_name }}</p>
                                </td>
                            </tr>
                        </table>

                        <!-- Status Table -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="font-family: Arial, sans-serif; border: 1px solid #eef0f7;">

                            <!-- Table Header -->
                            <tr style="background: #f5f6fb;">
                                <td width="70%" style="padding: 10px 14px; font-size: 9px; font-family: 'Courier New', monospace; letter-spacing: 2px; text-transform: uppercase; color: #b0b7c9; border-bottom: 1px solid #eef0f7;">
                                    Status
                                </td>
                                <td width="30%" style="padding: 10px 14px; font-size: 9px; font-family: 'Courier New', monospace; letter-spacing: 2px; text-transform: uppercase; color: #b0b7c9; border-bottom: 1px solid #eef0f7; text-align: right;">
                                    Count
                                </td>
                            </tr>

                            @foreach($status_counts as $status => $count)
                            <tr>
                                <td style="padding: 12px 14px; font-size: 13px; color: #374151; border-bottom: 1px solid #f5f6fb;">
                                    <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #fe6466; margin-right: 10px; vertical-align: middle; opacity: 0.7;"></span>
                                    {{ $status }}
                                </td>
                                <td style="padding: 12px 14px; font-size: 15px; color: #297bab; font-weight: 700; border-bottom: 1px solid #f5f6fb; text-align: right; font-family: 'Courier New', monospace;">
                                    {{ $count }}
                                </td>
                            </tr>
                            @endforeach

                        </table>

                        <!-- Note -->
                        <p style="margin: 26px 0 0; font-size: 13px; color: #9ca3af; line-height: 1.75; font-family: Arial, sans-serif;">
                            Questions? Reply directly to this email — we're happy to help.
                        </p>

                    </td>
                </tr>
            </table>

            <!-- Footer inside card -->
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding: 18px 44px; background: #f8f9ff; border-top: 1px solid #eef0f7;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="font-size: 13px; color: #374151; font-family: Arial, sans-serif;">
                                    <strong style="color: #297bab;">Code4Each Team</strong>
                                </td>
                                <td align="right" style="font-family: 'Courier New', monospace; font-size: 10px; color: #c8cdd8; letter-spacing: 1px;">
                                    code4each.com
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

        </td>
    </tr>

    <!-- Below card footer -->
    <tr>
        <td style="padding: 22px 0 0; text-align: center;">
            <p style="margin: 0; font-family: 'Courier New', monospace; font-size: 10px; color: #a0a8be; letter-spacing: 1px;">
                © {{ date('Y') }} Code4Each &nbsp;·&nbsp; All rights reserved
            </p>
        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>
