@extends('emails.layouts.main')

@section('content')

<p class="greeting">Bienvenue <strong>{{ $user->prenom }} </strong>,</p>
<p class="message">
    Félicitations ! Votre inscription sur <strong style="color: #30B59B;">LeyInvest</strong>
    a été effectuée avec succès.
</p>

<p class="message">
    Votre espace personnel est maintenant prêt. Vous pouvez dès à présent y accéder
    et débuter votre parcours d'investisseur en toute confiance.
</p>


<!-- Section "Vos prochaines étapes" -->
<div style="background: #F9FBFB; border-radius: 16px; padding: 28px; margin: 36px 0; border: 1px solid rgba(143, 215, 201, 0.2);">
    <h3 style="color: #30B59B; margin-bottom: 20px; font-size: 18px; text-align: center;">
        Prochaines étapes
    </h3>

    <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
        <div style="display: flex; align-items: flex-start; padding: 12px; background: white; border-radius: 12px; border-left: 4px solid #30B59B;">
            <div style="margin-right: 12px; color: #30B59B; font-weight: bold;">1</div>
            <div>
                <strong style="color: #1C2B28;">Compléter votre profil</strong>
                <p style="color: #5A6E69; margin-top: 4px; font-size: 14px;">
                    Renseignez vos informations pour des recommandations personnalisées
                </p>
            </div>
        </div>

        <div style="display: flex; align-items: flex-start; padding: 12px; background: white; border-radius: 12px; border-left: 4px solid #8FD7C9;">
            <div style="margin-right: 12px; color: #8FD7C9; font-weight: bold;">2</div>
            <div>
                <strong style="color: #1C2B28;">Découvrir nos opportunités</strong>
                <p style="color: #5A6E69; margin-top: 4px; font-size: 14px;">
                    Explorez les premières opportunités d'investissement adaptées à vos objectifs
                </p>
            </div>
        </div>

        <div style="display: flex; align-items: flex-start; padding: 12px; background: white; border-radius: 12px; border-left: 4px solid #30B59B;">
            <div style="margin-right: 12px; color: #30B59B; font-weight: bold;">3</div>
            <div>
                <strong style="color: #1C2B28;">Configurer vos préférences</strong>
                <p style="color: #5A6E69; margin-top: 4px; font-size: 14px;">
                    Personnalisez vos notifications.
                </p>
            </div>
        </div>
    </div>
</div>


@endsection
