<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F9FBFB; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center" style="padding: 45px 10px;">
                <table role="presentation" width="100%" style="max-width: 580px; background-color: #FEFEFE; border-radius: 20px; border: 1px solid #E8EFED; box-shadow: 0 10px 40px rgba(48, 181, 155, 0.08);" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="background: linear-gradient(165deg, #30B59B 0%, #2AA38B 100%); padding: 30px 30px; text-align: center;">
                            <h1 style="margin: 0; font-size: 30px; font-weight: 600; color: #FEFEFE; letter-spacing: -0.3px;">{{ config('app.name') }}</h1>
                            <div style="font-size: 14px; color: rgba(254, 254, 254, 0.85); margin-top: 6px; font-weight: 500; letter-spacing: 0.5px;">Finance Intelligente</div>
                        </td>
                    </tr>
                    <tr>
                    <td style="padding: 10px 35px;">
    @yield('content')
</td>
                    </tr>
                    <tr>
                        <td style="background-color: #F9FBFB; padding: 15px 30px; text-align: center; border-top: 1px solid #E8EFED;">
                            <div style="margin-bottom: 20px;">
                                <table role="presentation" align="center" cellspacing="0" cellpadding="0" border="0">
                                    <tr>
                                        <td align="center" style="background-color: #30B59B; width: 40px; height: 40px; border-radius: 50%; color: #FEFEFE; font-weight: bold; font-size: 14px; line-height: 40px;">LY</td>
                                    </tr>
                                </table>
                                <div style="margin-top: 10px;">
                                    <h4 style="margin: 0; color: #1C2B28; font-size: 15px;">Louis-Emmanuel YAO</h4>
                                    <p style="margin: 4px 0 0; color: #30B59B; font-size: 13px;">Fondateur & CEO de LeyInvest</p>
                                </div>
                            </div>
                            <p style="margin: 0; font-size: 12px; color: #5A6E69; opacity: 0.8;">© {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
                            <p style="margin: 5px 0 0; font-size: 11px; color: #5A6E69;">Cet email a été envoyé à {{ $user->email ?? 'vous' }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
