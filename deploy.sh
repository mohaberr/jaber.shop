#!/bin/bash
# Script de déploiement Jabir Facturation — fac-ja.shop
# À exécuter sur le serveur Hostinger via SSH

set -e
DEPLOY_DIR="$HOME/public_html"

echo "=== 1. Extraction du projet ==="
cd "$HOME"
unzip -o jabir.zip -d "$DEPLOY_DIR"
rm -f jabir.zip

echo "=== 2. Installation des dépendances Composer ==="
cd "$DEPLOY_DIR"
composer install --no-dev --optimize-autoloader --no-interaction

echo "=== 3. Configuration .env ==="
if [ ! -f "$DEPLOY_DIR/.env" ]; then
    cp "$DEPLOY_DIR/.env.example" "$DEPLOY_DIR/.env"
    echo ""
    echo "!!! ATTENTION: Editer .env avec vos identifiants MySQL !!!"
    echo "nano $DEPLOY_DIR/.env"
    echo ""
    echo "Puis relancer ce script ou exécuter les commandes suivantes manuellement."
    exit 1
fi

echo "=== 4. Génération de la clé applicative ==="
php artisan key:generate --force

echo "=== 5. Migrations MySQL ==="
php artisan migrate --force

echo "=== 6. Seeder (admin + paramètres) ==="
php artisan db:seed --force

echo "=== 7. Lien symbolique storage ==="
php artisan storage:link

echo "=== 8. Optimisation production ==="
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== 9. Permissions ==="
chmod -R 775 storage bootstrap/cache
find storage -type f -exec chmod 664 {} \;

echo ""
echo "=== Déploiement terminé ! ==="
echo "Site disponible sur: https://fac-ja.shop"
echo "Admin: admin@jabir.com / secret123  <-- CHANGER CE MOT DE PASSE !"
