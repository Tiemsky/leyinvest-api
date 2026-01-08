@extends('emails.layouts.main')

@section('content')
<p class="greeting">Bonjour <strong>{{ $user->prenom }}</strong>,</p>

<p class="message">
    Pour finaliser votre action, voici votre code de vérification.
</p>

<div class="otp-container">
    <span class="otp-label">Code de vérification</span>
    <div class="otp-box">
        <div class="otp-code">
          @foreach(str_split($otp) as $digit)
              <span>{{ $digit }}</span>
          @endforeach
        </div>
    </div>
    <p class="otp-expiry">
        <span style="color: var(--primary);">⏱</span>
        Valable pendant <strong>10 minutes</strong>
    </p>
</div>

<div class="security-note">
    <p>Sécurité de votre compte</p>
    <p>✓ Ce code est strictement personnel et confidentiel.</p>
    <p>✓ Notre équipe ne vous demandera jamais de le partager.</p>
    <p>✓ Si vous n'êtes pas à l'origine de cette demande, contactez immédiatement notre support</p>
</div>

<div class="signature">
    <p>À bientôt,<br>
    <strong>L'équipe LeyInvest</strong></p>
</div>
@endsection
