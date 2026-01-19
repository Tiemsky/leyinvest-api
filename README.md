🚀 LeyInvest - Fintech BRVM Sync Engine
📌 Présentation du Projet

LeyInvest est une plateforme Fintech spécialisée dans le suivi des marchés financiers de la BRVM (Bourse Régionale des Valeurs Mobilières).

Ce dépôt contient le moteur de synchronisation (Backend). Il orchestre la récupération des données de marché via un scraper FastAPI (Python), traite les données financières complexes (actions, indices sectoriels, indicateurs de marché) et les expose via une API REST sécurisée par Webhooks.
Architecture Technique
🛠 Stack Technique

    Framework: Laravel 12 (PHP 8.3)

    Base de données: PostgreSQL 16 (Données financières persistantes)

    Asynchronisme: Redis (Gestion des Files d'attente / Queues)

    Infrastructure: Docker & Docker Compose (Environnements Local & Prod)

    Outils de Dev: pgAdmin (DB), Mailpit (Emails), Redis Commander (Cache)

🚀 Installation & Démarrage Rapide

Le projet utilise un Makefile pour simplifier toutes les opérations Docker complexes.

1. Pré-requis

    Docker & Docker Compose

    make installé sur votre système

2. Initialisation complète
   Bash

# Clonez le projet

git clone [url-du-repo]
cd leyinvest-backend

# Setup automatique (Install deps + Docker Up + Migrations + Keys)

make setup

3. Accès aux outils (Local)

    API Laravel: http://localhost:8000

    pgAdmin (Base de données): http://localhost:8080 (Login: tiafranck@leyinvestcom.ci)

    Mailpit (Tests Emails): http://localhost:8025

    Redis Commander: http://localhost:8081

🕹 Commandes Utiles (Makefile)
Gestion des Containers

    make up : Démarre l'environnement.

    make down : Arrête tous les services.

    make restart : Redémarre les containers.

    make logs-app : Affiche les logs Laravel en temps réel.

Base de Données

    make migrate : Exécute les migrations.

    make fresh : Réinitialise totalement la base avec les Seeders.

    make shell-db : Accède directement au terminal PostgreSQL.

Queues & Synchronisation

    make logs-queue : Surveille le Worker qui traite les synchronisations BRVM.

    make queue-restart : Redémarre le processeur de tâches après une modification de code.

📡 Synchronisation BRVM (Webhook)

Le système reçoit des signaux de l'API Python pour mettre à jour les cours de la bourse.

Exemple de déclenchement manuel du Webhook :
Bash

curl -X POST http://localhost:8000/api/webhooks/brvm-sync \
 -H "X-Webhook-Token: [TON_TOKEN]" \
 -H "Content-Type: application/json" \
 -d '{"data_type": "all", "data": {"status": "trigger"}}'

🏗 Structure Docker

Le projet est divisé en plusieurs services optimisés :

    App: Serveur web PHP-FPM / Nginx.

    Worker: Processeur de tâches en arrière-plan (Queue Redis).

    Scheduler: Gère les tâches planifiées (ex: Nettoyage des logs à minuit).

    Postgres: Stockage relationnel haute performance.

    Redis: Broker pour les queues et le cache.

🔐 Sécurité & Permissions

Si vous rencontrez des problèmes de droits sur Linux/WSL :
Bash

make permissions

# ou pour WSL spécifiquement

make fix-wsl-permissions

👥 Équipe & Support

    Lead Developer: Franck Tia

    Organisation: LeyInvest Fintech
