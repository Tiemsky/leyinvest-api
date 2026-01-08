@extends('emails.layouts.main')

@section('content')
<div style="text-align: center; margin-bottom: 32px;">
    <h2 style="color: #1C2B28; margin: 0; font-size: 20px; font-weight: 600;">Réinitialisation de mot de passe</h2>
</div>

<p style="font-size: 16px; color: #3A4A46;">Bonjour <strong>{{ $user->prenom }}</strong>,</p>
<p style="font-size: 16px; color: #3A4A46; line-height: 1.7;">Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte. Utilisez le code ci-dessous pour continuer.</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin: 40px 0; text-align: center;">
    <tr>
        <td align="center">
            <div style="background-color: #F9FBFB; border-radius: 18px; padding: 35px; border: 2px solid rgba(48, 181, 155, 0.3); max-width: 400px; margin: 0 auto;">
                <span style="color: #5A6E69; font-size: 12px; font-weight: 600; letter-spacing: 1px; display: block; margin-bottom: 15px;">CODE DE RÉINITIALISATION</span>
                <div style="font-size: 44px; font-weight: bold; letter-spacing: 10px; color: #1C2B28; font-family: 'Courier New', Courier, monospace;">{{ $otp }}</div>
                <p style="margin: 20px 0 0; color: #30B59B; font-size: 13px;">⏱ Expire dans 10 min</p>
            </div>
        </td>
    </tr>
</table>

<div style="background-color: #FFF5F5; border-radius: 12px; padding: 20px; border: 1px solid rgba(255, 107, 107, 0.2); text-align: left;">
    <p style="color: #1C2B28; font-weight: 600; margin: 0 0 8px;">⚠️ Vous n'avez pas fait cette demande ?</p>
    <p style="color: #5A6E69; font-size: 14px; margin: 0; line-height: 1.5;">Ignorez simplement cet email. Votre mot de passe actuel restera inchangé.</p>
</div>
@endsection
