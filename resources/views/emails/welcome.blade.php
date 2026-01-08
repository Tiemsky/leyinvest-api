@extends('emails.layouts.main')

@section('content')
    <p style="font-size: 20px; color: #1C2B28; font-weight: 600; margin-bottom: 20px;">
        Bienvenue <strong>{{ $user->prenom }}</strong>,
    </p>

    <p style="font-size: 16px; color: #3A4A46; line-height: 1.7; margin-bottom: 16px;">
        Félicitations ! Votre inscription sur <strong style="color: #30B59B;">{{ config('app.name') }}</strong> a été effectuée avec succès. 🎉
    </p>

    <p style="font-size: 16px; color: #3A4A46; line-height: 1.7; margin-bottom: 30px;">
        Votre espace personnel est maintenant prêt. Vous pouvez dès à présent y accéder et débuter votre parcours d'investisseur en toute confiance.
    </p>

    {{-- Section Prochaines étapes --}}
    <div style="background-color: #F9FBFB; border-radius: 16px; padding: 25px; margin: 30px 0; border: 1px solid rgba(143, 215, 201, 0.2);">
        <h3 style="color: #30B59B; margin: 0 0 20px; font-size: 18px; text-align: center;">
            ✨ Prochaines étapes
        </h3>

        <div style="background: white; padding: 15px; border-radius: 12px; border-left: 4px solid #30B59B; margin-bottom: 12px;">
            <strong style="color: #1C2B28; font-size: 15px;">1. Compléter votre profil</strong>
            <p style="color: #5A6E69; margin: 4px 0 0; font-size: 14px; line-height: 1.5;">
                Renseignez vos informations pour des recommandations personnalisées
            </p>
        </div>

        <div style="background: white; padding: 15px; border-radius: 12px; border-left: 4px solid #8FD7C9; margin-bottom: 12px;">
            <strong style="color: #1C2B28; font-size: 15px;">2. Découvrir nos opportunités</strong>
            <p style="color: #5A6E69; margin: 4px 0 0; font-size: 14px; line-height: 1.5;">
                Explorez les premières opportunités d'investissement adaptées à vos objectifs
            </p>
        </div>

        <div style="background: white; padding: 15px; border-radius: 12px; border-left: 4px solid #30B59B;">
            <strong style="color: #1C2B28; font-size: 15px;">3. Configurer vos préférences</strong>
            <p style="color: #5A6E69; margin: 4px 0 0; font-size: 14px; line-height: 1.5;">
                Personnalisez vos notifications et ajustez vos paramètres de sécurité
            </p>
        </div>
    </div>

    {{-- Section Ce qui vous attend --}}
    <div style="background: linear-gradient(135deg, rgba(48, 181, 155, 0.08) 0%, rgba(143, 215, 201, 0.08) 100%); border-radius: 16px; padding: 25px; margin: 30px 0;">
        <h3 style="color: #1C2B28; margin: 0 0 20px; font-size: 18px; text-align: center;">
            🚀 Ce qui vous attend
        </h3>

        <div style="margin-bottom: 15px;">
            <div style="display: inline-block; width: 32px; height: 32px; background-color: #30B59B; border-radius: 8px; text-align: center; line-height: 32px; vertical-align: middle; margin-right: 12px;">
                <span style="color: white; font-size: 18px;">📊</span>
            </div>
            <div style="display: inline-block; vertical-align: middle; width: calc(100% - 50px);">
                <strong style="color: #1C2B28; font-size: 15px;">Tableau de bord personnalisé</strong>
                <p style="color: #5A6E69; margin: 2px 0 0; font-size: 14px; line-height: 1.5;">
                    Suivez vos investissements et performances en temps réel
                </p>
            </div>
        </div>

        <div style="margin-bottom: 15px;">
            <div style="display: inline-block; width: 32px; height: 32px; background-color: #8FD7C9; border-radius: 8px; text-align: center; line-height: 32px; vertical-align: middle; margin-right: 12px;">
                <span style="color: white; font-size: 18px;">🎓</span>
            </div>
            <div style="display: inline-block; vertical-align: middle; width: calc(100% - 50px);">
                <strong style="color: #1C2B28; font-size: 15px;">Ressources éducatives</strong>
                <p style="color: #5A6E69; margin: 2px 0 0; font-size: 14px; line-height: 1.5;">
                    Accédez à nos guides et formations pour investisseurs
                </p>
            </div>
        </div>

        <div style="margin-bottom: 15px;">
            <div style="display: inline-block; width: 32px; height: 32px; background-color: #30B59B; border-radius: 8px; text-align: center; line-height: 32px; vertical-align: middle; margin-right: 12px;">
                <span style="color: white; font-size: 18px;">🔔</span>
            </div>
            <div style="display: inline-block; vertical-align: middle; width: calc(100% - 50px);">
                <strong style="color: #1C2B28; font-size: 15px;">Alertes intelligentes</strong>
                <p style="color: #5A6E69; margin: 2px 0 0; font-size: 14px; line-height: 1.5;">
                    Recevez des notifications sur les opportunités qui vous correspondent
                </p>
            </div>
        </div>

        <div>
            <div style="display: inline-block; width: 32px; height: 32px; background-color: #8FD7C9; border-radius: 8px; text-align: center; line-height: 32px; vertical-align: middle; margin-right: 12px;">
                <span style="color: white; font-size: 18px;">💬</span>
            </div>
            <div style="display: inline-block; vertical-align: middle; width: calc(100% - 50px);">
                <strong style="color: #1C2B28; font-size: 15px;">Support dédié</strong>
                <p style="color: #5A6E69; margin: 2px 0 0; font-size: 14px; line-height: 1.5;">
                    Notre équipe est disponible pour répondre à vos questions
                </p>
            </div>
        </div>
    </div>

    {{-- Bouton CTA --}}
    <div style="text-align: center; margin: 35px 0;">
        <a href="{{ config('app.frontend_url') }}" style="display: inline-block; background: linear-gradient(135deg, #30B59B 0%, #8FD7C9 100%); color: white; padding: 16px 40px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 16px; box-shadow: 0 4px 15px rgba(48, 181, 155, 0.3); transition: transform 0.2s;">
            🎯 Accéder à mon espace
        </a>
    </div>

    {{-- Section d'aide --}}
    <div style="background-color: #FFF8F0; border-left: 4px solid #F59E0B; padding: 20px; margin: 30px 0; border-radius: 8px;">
        <h3 style="color: #B45309; font-size: 16px; margin: 0 0 10px;">
            💡 Besoin d'aide ?
        </h3>
        <p style="color: #92400E; font-size: 14px; margin: 0; line-height: 1.6;">
            Si vous avez des questions ou besoin d'assistance, notre équipe support est disponible
            pour vous accompagner. N'hésitez pas à nous contacter à
            <a href="mailto:support@{{ parse_url(config('app.frontend_url'), PHP_URL_HOST) }}" style="color: #30B59B; text-decoration: none; font-weight: 600;">
                support@{{ parse_url(config('app.frontend_url'), PHP_URL_HOST) }}
            </a>
        </p>
    </div>

    {{-- Message de fin --}}
    <p style="font-size: 14px; color: #5A6E69; line-height: 1.6; margin-top: 30px;">
        Merci de faire confiance à <strong style="color: #30B59B;">{{ config('app.name') }}</strong>.
        Nous nous engageons à vous offrir la meilleure expérience possible et à vous accompagner
        dans la réalisation de vos objectifs d'investissement.
    </p>

    <p style="font-size: 14px; color: #5A6E69; margin-top: 20px;">
        À très bientôt,<br>
        <strong style="color: #1C2B28;">L'équipe {{ config('app.name') }}</strong>
    </p>
@endsection
