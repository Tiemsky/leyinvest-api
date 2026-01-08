@extends('emails.layouts.main')
@section('content')
<!-- En-tête -->
<div style="text-align: center; margin-bottom: 32px;">
    <h2 style="color: #1C2B28; margin-top: 16px; font-size: 20px; font-weight: 600;">
        Réinitialisation de mot de passe
    </h2>
</div>

<p class="greeting">Bonjour <strong>{{ $user->prenom }} </strong>,</p>

<p class="message">
    Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.
    Utilisez le code ci-dessous pour continuer.
</p>

<!-- Code OTP -->
<div style="text-align: center; margin: 40px 0;">
    <div style="margin-bottom: 16px;">
        <span style="color: #5A6E69; font-size: 13px; font-weight: 600; letter-spacing: 0.5px;">
            CODE DE RÉINITIALISATION
        </span>
    </div>

    <div style="background: linear-gradient(135deg, #FEFEFE 0%, #F9FBFB 100%);
                border-radius: 18px;
                padding: 36px 28px;
                border: 2px solid rgba(48, 181, 155, 0.3);
                max-width: 420px;
                margin: 0 auto;
                position: relative;">

        <!-- Indicateur "Nouveau" -->
        <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%);">
            <div style="background: #30B59B; color: white; padding: 4px 16px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                CODE
            </div>
        </div>

        <div style="font-size: 48px;
                    font-weight: 700;
                    letter-spacing: 10px;
                    color: #1C2B28;
                    font-family: 'SF Mono', Monaco, monospace;
                    text-align: center;
                    line-height: 1.2;
                    padding: 8px 0;">
                                @foreach(str_split($otp) as $digit)
            <span style="display: inline-block; min-width: 40px; text-align: center;">{{ $digit }}</span>
            @endforeach

        </div>

        <!-- Détails du code -->
        <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid rgba(143, 215, 201, 0.3);">
            <div style="display: flex; justify-content: center; gap: 24px;">
                <div style="text-align: center;">
                    <div style="color: #30B59B; font-size: 20px;">⏱️</div>
                    <div style="color: #5A6E69; font-size: 13px; margin-top: 4px;">
                        Expire dans<br><strong>{{ $expiry ?? '10' }} min</strong>
                    </div>
                </div>

                <div style="text-align: center;">
                    <div style="color: #30B59B; font-size: 20px;">🆕</div>
                    <div style="color: #5A6E69; font-size: 13px; margin-top: 4px;">
                        Code généré à<br><strong>{{ now()->format('H:i') }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Avertissement -->
<div style="background: rgba(255, 245, 245, 0.9);
            border-radius: 12px;
            padding: 20px;
            margin: 32px 0;
            border: 1px solid rgba(255, 107, 107, 0.2);">
    <div style="display: flex; align-items: flex-start; gap: 12px;">
        <div style="color: #FF6B6B; font-size: 20px;">⚠️</div>
        <div>
            <p style="color: #1C2B28; font-weight: 600; margin-bottom: 8px;">
                Vous n'avez pas fait cette demande ?
            </p>
            <p style="color: #5A6E69; font-size: 14px; margin: 0; line-height: 1.5;">
                Ignorez simplement cet email. Votre mot de passe actuel restera inchangé.
            </p>
        </div>
    </div>
</div>

@endsection
