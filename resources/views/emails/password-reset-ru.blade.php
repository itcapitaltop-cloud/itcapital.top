@php($buttonColor = '#B4FF59')
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('email_password_reset_title') }}</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;
             background:#17162d;color:#ffffff;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding:32px 0;background:#17162d;">
            <img src="{{ vite()->icon('/main/logotype.png') }}" width="180" alt="Logo">
        </td>
    </tr>

    <tr>
        <td align="center" style="padding:40px 24px;background:#17162d;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                   style="max-width:600px;width:100%;">
                <tr>
                    <td style="font-size:20px;font-weight:bold;text-align:center;padding-bottom:24px;">
                        {{ __('email_password_reset_subject') }}
                    </td>
                </tr>
                <tr>
                    <td style="font-size:16px;line-height:1.5;text-align:center;padding-bottom:32px;">
                        {{ __('email_password_reset_body', [
                            'minutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60)
                        ]) }}
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding-bottom:32px;">
                        <a href="{{ $url }}"
                           style="display:inline-block;padding:14px 32px;font-size:16px;font-weight:bold;
                                  background:{{ $buttonColor }};color:#000;text-decoration:none;border-radius:8px;">
                            {{ __('email_password_reset_button') }}
                        </a>
                    </td>
                </tr>

                <tr>
                    <td style="font-size:14px;line-height:1.4;text-align:center;color:#cccccc;">
                        {{ __('email_password_reset_fallback') }}<br>
                        <a href="{{ $url }}" style="color:#B4FF59;word-break:break-all;">{{ $url }}</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td align="center" style="padding:24px;background:#17162d;font-size:12px;color:#777777;">
            © {{ date('Y') }} IT Capital. {{ __('copyright') }}.
        </td>
    </tr>
</table>
</body>
</html>
