<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Email</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family: Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding:20px 0;">
<tr>
<td align="center">

<!-- Main Container -->
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.08);">

    <!-- Header -->
    <tr>
        <td style="background:linear-gradient(135deg, #4e73df, #1cc88a); padding:25px; text-align:center; color:#ffffff;">
            <h1 style="margin:0; font-size:24px;">
                {!! $subject !!}
            </h1>
        </td>
    </tr>
@php
    $content = preg_replace(
        '/\{\{\s*banner_image\s*\}\}/',
        $banner_img 
            ? '<img src="' . $message->embed(storage_path('app/public/' . $banner_img)) . '" 
                    style="max-width:100%;border-radius:6px;margin:6px 0;display:block;" 
                    alt="Banner image">'
            : '',
        $content
    );
@endphp

    <!-- Body -->
    <tr>
        <td style="padding:30px; color:#333333;">

            <div style="font-size:15px; line-height:1.6;">
                {!! $content !!}
            </div>

        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="background:#ffffff; ">
            <table width="100%">
                <tr>
                    <td align="left">
                        <img src="{{ $message->embed(public_path('assets/img/code4each_logo.png')) }}" 
                             alt="Company Logo" 
                             width="120" 
                             style="display:block; margin-bottom:8px;margin-left: 21px;">
                    </td>
                </tr>
            </table>
        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>