@extends('emails.layouts.main')

@section('content')
<p style="font-size: 16px; color: #3A4A46;">Bonjour <strong>{{ $user->prenom }} </strong>,</p>
<p style="font-size: 16px; color: #3A4A46; line-height: 1.7;">Pour finaliser votre action, voici votre code de vérification.</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin: 35px 0;">
    <tr>
        <td align="center" style="background: linear-gradient(145deg, #FEFEFE 0%, #E8F6F3 100%); border-radius: 12px; padding: 32px; border: 1px solid rgba(48, 181, 155, 0.2);">
            <span style="color: #5A6E69; font-size: 13px; font-weight: 600; text-transform: uppercase;">Code de vérification</span>
            <div style="font-size: 42px; font-weight: 700; letter-spacing: 8px; color: #1C2B28; margin: 16px 0;">{{ $otp }}</div>
            <p style="margin: 0; color: #5A6E69; font-size: 14px;"><span style="color: #30B59B;">⏱</span> Valable pendant <strong>10 minutes</strong></p>
        </td>
    </tr>
</table>

<div style="background-color: #F9FBFB; border-radius: 8px; padding: 20px; border: 1px solid rgba(143, 215, 201, 0.3); font-size: 14px; color: #3A4A46;">
    <p style="margin: 0 0 10px; font-weight: bold; color: #30B59B;">🔒 Sécurité de votre compte</p>
    <p style="margin: 5px 0;">✓ Ce code est strictement personnel et confidentiel.</p>
    <p style="margin: 5px 0;">✓ Notre équipe ne vous demandera jamais de le partager.</p>
    <p style="margin: 5px 0;">✓ Si vous n'êtes pas à l'origine de cette demande, contactez notre support.</p>
</div>
@endsection
