#!/bin/bash
while true; do
  echo "🔄 Démarrage du worker de queue..."
  php artisan queue:work --queue=default --tries=3 --sleep=2 --max-jobs=1000
  echo "⚠️ Worker arrêté. Redémarrage dans 5s..."
  sleep 5
done
