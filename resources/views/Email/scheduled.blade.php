<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0"
                       style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;width:100%;">

                    {{-- Banner image if exists --}}
                    @if($bannerUrl)
                    <tr>
                        <td>
                            <img src="{{ $bannerUrl }}" width="600" alt="Banner"
                                 style="width:100%;max-width:600px;display:block;">
                        </td>
                    </tr>
                    @else
                    <tr>
                        <td style="padding:10px 40px 4px;background:#7F77DD;">
                            <p style="color:#ffffff;font-size:18px;font-weight:bold;margin:0;">
                                {{ config('app.name') }}
                            </p>
                        </td>
                    </tr>
                    @endif

                    {{-- Email body --}}
                    <tr>
                        <td style="padding:30px 40px;font-size:15px;color:#333333;line-height:1.8;">
                            {!! nl2br(e($body)) !!}
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:16px 40px;background:#f8f8f8;border-top:1px solid #eeeeee;
                                   font-size:12px;color:#999999;text-align:center;">
                            Sent via {{ config('app.name') }} &middot; Unsubscribe
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
