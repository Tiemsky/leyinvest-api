@extends('emails.layouts.main')

@section('content')
<div style="text-align: center; margin-bottom: 32px;">
    <h2 style="color: #1C2B28; margin: 0; font-size: 22px; font-weight: 600;">Nouveau code envoyé</h2>
    <p style="color: #5A6E69; margin-top: 8px; font-size: 15px;">Votre demande a été traitée avec succès</p>
</div>

<p style="font-size: 16px; color: #3A4A46;">Bonjour <strong style="color: #30B59B;">{{ $user->prenom }}</strong>,</p>
<p style="font-size: 16px; color: #3A4A46; line-height: 1.7;">Suite à votre demande, nous venons de générer un nouveau code de vérification pour votre compte.</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin: 30px 0; text-align: center;">
    <tr>
        <td align="center">
            <div style="display: inline-block; padding: 8px 20px; background-color: rgba(48, 181, 155, 0.08); border-radius: 10px;">
                <span style="color: #30B59B; font-size: 13px; font-weight: 600; letter-spacing: 0.8px;">VOTRE NOUVEAU CODE</span>
            </div>
            <div style="background-color: #F9FBFB; border-radius: 18px; padding: 30px; border: 2px solid rgba(48, 181, 155, 0.3); max-width: 400px; margin: 0 auto;">
                <div style="font-size: 44px; font-weight: bold; letter-spacing: 10px; color: #1C2B28; font-family: 'Courier New', Courier, monospace;">
                    {{ $otp }}
                </div>
                <div style="margin-top: 25px; border-top: 1px solid rgba(143, 215, 201, 0.3); padding-top: 20px;">
                    <p style="margin: 0; color: #5A6E69; font-size: 13px;">⏱ Expire dans <strong>{{ $expiry ?? '10' }} min</strong> | 🆕 à <strong>{{ now()->format('H:i') }}</strong></p>
                </div>
            </div>
        </td>
    </tr>
</table>

<div style="background-color: rgba(143, 215, 201, 0.08); border-radius: 14px; padding: 22px; margin: 32px 0; border-left: 4px solid #8FD7C9; text-align: left;">
    <p style="color: #1C2B28; font-weight: 600; margin-bottom: 8px; font-size: 16px;">💡 Information importante</p>
    <p style="color: #5A6E69; font-size: 14px; margin: 0; line-height: 1.6;">
        L'ancien code a été <strong>désactivé</strong>. Seul ce nouveau code est valable. Pour des raisons de sécurité, vous pouvez demander un nouveau code au maximum 3 fois toutes les 24 heures.
    </p>
</div>
@endsection
