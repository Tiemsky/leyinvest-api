@extends('emails.layouts.main')

@section('content')
<p style="font-size: 20px; color: #1C2B28; font-weight: 600;">Bienvenue <strong>{{ $user->prenom }}</strong>,</p>
<p style="font-size: 16px; color: #3A4A46; line-height: 1.7;">
    Félicitations ! Votre inscription sur <strong style="color: #30B59B;">{{ config('app.name') }}</strong> a été effectuée avec succès.
</p>
<p style="font-size: 16px; color: #3A4A46; line-height: 1.7;">
    Votre espace personnel est maintenant prêt. Vous pouvez dès à présent y accéder et débuter votre parcours d'investisseur en toute confiance.
</p>

<div style="background-color: #F9FBFB; border-radius: 16px; padding: 25px; margin: 30px 0; border: 1px solid rgba(143, 215, 201, 0.2);">
    <h3 style="color: #30B59B; margin: 0 0 20px; font-size: 18px; text-align: center;">Prochaines étapes</h3>

    <div style="background: white; padding: 15px; border-radius: 12px; border-left: 4px solid #30B59B; margin-bottom: 12px;">
        <strong style="color: #1C2B28;">1. Compléter votre profil</strong>
        <p style="color: #5A6E69; margin: 4px 0 0; font-size: 14px;">Renseignez vos informations pour des recommandations personnalisées</p>
    </div>

    <div style="background: white; padding: 15px; border-radius: 12px; border-left: 4px solid #8FD7C9; margin-bottom: 12px;">
        <strong style="color: #1C2B28;">2. Découvrir nos opportunités</strong>
        <p style="color: #5A6E69; margin: 4px 0 0; font-size: 14px;">Explorez les premières opportunités d'investissement adaptées à vos objectifs</p>
    </div>

    <div style="background: white; padding: 15px; border-radius: 12px; border-left: 4px solid #30B59B;">
        <strong style="color: #1C2B28;">3. Configurer vos préférences</strong>
        <p style="color: #5A6E69; margin: 4px 0 0; font-size: 14px;">Personnalisez vos notifications.</p>
    </div>
</div>
@endsection
