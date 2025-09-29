@php($buttonColor = '#B4FF59')
    <!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('password_changed') }}</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#17162d;color:#ffffff;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <!-- шапка ------------------------------------------------------------- -->
    <tr>
        <td align="center" style="padding:32px 0;">
            <img src="{{ vite()->icon('/main/logotype.png') }}" width="180" alt="IT Capital">
        </td>
    </tr>

    <!-- контент ----------------------------------------------------------- -->
    <tr>
        <td align="center" style="padding:40px 24px;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
                <tr>
                    <td style="font-size:20px;font-weight:bold;text-align:center;padding-bottom:24px;">
                        {{ __('password_changed_account') }}
                    </td>
                </tr>

                <tr>
                    <td style="font-size:16px;line-height:1.5;text-align:center;padding-bottom:32px;">
                        {!! __('password_changed_notify') !!}
                        <a href="https://t.me/ITCAPITALTOP" style="color:#B4FF59;">{{ __('contact_support') }}</a>.
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding-bottom:32px;">
                        <a href="{{ route('login') }}"
                           style="display:inline-block;padding:14px 32px;font-size:16px;font-weight:bold;
                                  background:{{ $buttonColor }};color:#000;text-decoration:none;border-radius:8px;">
                            {{ __('login_to_your_account') }}
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- подвал ------------------------------------------------------------ -->
    <tr>
        <td align="center" style="padding:24px;font-size:12px;color:#777;">
            © {{ date('Y') }} IT Capital. {{ __('copyright') }}.
        </td>
    </tr>
</table>
</body>
</html>
