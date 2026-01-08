@extends('emails.layouts.main')

@section('content')
<!-- En-tête visuelle -->
<div style="text-align: center; margin-bottom: 32px;">
    <h2 style="color: #1C2B28; margin: 0; font-size: 22px; font-weight: 600;">
        Nouveau code envoyé
    </h2>
    <p style="color: #5A6E69; margin-top: 8px; font-size: 15px;">
        Votre demande a été traitée avec succès
    </p>
</div>

<p class="greeting">Bonjour <strong> {{ $user->prenom }}  </strong>,</p>

<p class="message">
    Suite à votre demande, nous venons de générer un nouveau code de vérification pour votre compte.
</p>

<!-- Nouveau code en évidence -->
<div style="text-align: center; margin: 40px 0;">
    <div style="margin-bottom: 20px;">
        <div style="display: inline-block; padding: 8px 20px; background: rgba(48, 181, 155, 0.08); border-radius: 20px;">
            <span style="color: #30B59B; font-size: 14px; font-weight: 600;">
                VOTRE NOUVEAU CODE
            </span>
        </div>
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
                NOUVEAU
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

<!-- Information sur le renvoi -->
<div style="background: rgba(143, 215, 201, 0.08);
            border-radius: 14px;
            padding: 22px;
            margin: 32px 0;
            border-left: 4px solid #8FD7C9;">
    <div style="display: flex; align-items: flex-start; gap: 16px;">
        <div style="color: #30B59B; font-size: 24px; min-width: 40px;">💡</div>
        <div>
            <p style="color: #1C2B28; font-weight: 600; margin-bottom: 8px; font-size: 16px;">
                Information importante
            </p>
            <p style="color: #5A6E69; font-size: 15px; margin: 0; line-height: 1.6;">
                L'ancien code a été <strong>désactivé</strong>. Seul ce nouveau code est valable.
                Pour des raisons de sécurité, vous pouvez demander un nouveau code au maximum
                3 fois toutes les 24 heures.
            </p>
        </div>
    </div>
</div>

<!-- Étapes à suivre -->
<div style="margin: 40px 0;">
    <h3 style="color: #1C2B28; text-align: center; margin-bottom: 24px; font-size: 17px;">
        Comment utiliser ce code ?
    </h3>

    <div style="display: grid; grid-template-columns: 1fr; gap: 12px; max-width: 480px; margin: 0 auto;">
        <div style="display: flex; align-items: center; background: white; padding: 16px; border-radius: 12px; border: 1px solid rgba(143, 215, 201, 0.2);">
            <div style="background: #30B59B; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 16px; font-weight: 600;">1</div>
            <div>
                <p style="color: #1C2B28; font-weight: 600; margin: 0;">Copiez le code</p>
                <p style="color: #5A6E69; font-size: 14px; margin: 4px 0 0 0;">Sélectionnez et copiez les 6 chiffres</p>
            </div>
        </div>

        <div style="display: flex; align-items: center; background: white; padding: 16px; border-radius: 12px; border: 1px solid rgba(143, 215, 201, 0.2);">
            <div style="background: #8FD7C9; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 16px; font-weight: 600;">2</div>
            <div>
                <p style="color: #1C2B28; font-weight: 600; margin: 0;">Retournez sur la page</p>
                <p style="color: #5A6E69; font-size: 14px; margin: 4px 0 0 0;">Ouvrez votre navigateur avec la page de vérification</p>
            </div>
        </div>

        <div style="display: flex; align-items: center; background: white; padding: 16px; border-radius: 12px; border: 1px solid rgba(143, 215, 201, 0.2);">
            <div style="background: #30B59B; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 16px; font-weight: 600;">3</div>
            <div>
                <p style="color: #1C2B28; font-weight: 600; margin: 0;">Collez et validez</p>
                <p style="color: #5A6E69; font-size: 14px; margin: 4px 0 0 0;">Saisissez le code dans le champ prévu</p>
            </div>
        </div>
    </div>
</div>

@endsection
