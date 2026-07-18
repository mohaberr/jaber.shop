# Déploiement sur Hostinger — fac-ja.shop

## Pré-requis Hostinger
- **Hébergement**: VPS ou Business Hosting (support Laravel requis)
- **PHP**: 8.3 minimum (configurable dans hPanel)
- **MySQL**: disponible dans hPanel → Databases
- **SSH**: activé (hPanel → SSH Access)

---

## Étape 1 — Créer la base de données MySQL sur Hostinger

1. Se connecter à **hPanel** (hpanel.hostinger.com)
2. Aller dans **Databases → MySQL Databases**
3. Créer une base de données :
   - Nom : `facja_db` (exemple)
   - Utilisateur : `facja_user`
   - Mot de passe : (fort, noter quelque part)
4. Noter : **host** (souvent `127.0.0.1`), **nom**, **user**, **password**

---

## Étape 2 — Uploader les fichiers du projet

### Option A : via Git (recommandé si VPS)
```bash
ssh user@fac-ja.shop
git clone https://github.com/VOTRE_REPO.git /home/user/public_html
```

### Option B : via FTP/File Manager (hPanel)
1. Compresser le projet en ZIP (tout sauf le dossier `vendor/` et `node_modules/`)
2. Uploader via **hPanel → File Manager** dans `/home/user/public_html/`
3. Extraire le ZIP

**Important** : le dossier `public/` de Laravel doit être le dossier racine du site.
Sur Hostinger, configurer le **Document Root** vers `/home/user/public_html/public`
(hPanel → Websites → Manage → File Manager → Advanced → Document Root)

---

## Étape 3 — Créer le fichier .env sur le serveur

Via SSH ou File Manager, créer `/home/user/public_html/.env` :

```env
APP_NAME="Jabir Facturation"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://fac-ja.shop

APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=facja_db
DB_USERNAME=facja_user
DB_PASSWORD=votre_mot_de_passe

SESSION_DRIVER=database
SESSION_LIFETIME=120

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_SCHEME=ssl
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=noreply@fac-ja.shop
MAIL_PASSWORD=votre_mot_de_passe_email
MAIL_FROM_ADDRESS="noreply@fac-ja.shop"
MAIL_FROM_NAME="Jabir Facturation"
```

---

## Étape 4 — Commandes à exécuter via SSH

```bash
cd /home/user/public_html

# Installer les dépendances PHP (sans dev)
composer install --no-dev --optimize-autoloader

# Générer la clé applicative
php artisan key:generate

# Appliquer toutes les migrations MySQL
php artisan migrate --force

# Charger les données initiales (admin + paramètres)
php artisan db:seed --force

# Créer le lien symbolique pour les fichiers uploadés (logos, images)
php artisan storage:link

# Mettre en cache les configs, routes et vues (performance)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Étape 5 — Configurer le SSL (HTTPS)

1. hPanel → **SSL** → activer **Let's Encrypt gratuit** pour `fac-ja.shop`
2. Activer la redirection automatique HTTP → HTTPS

---

## Étape 6 — Vérification finale

Ouvrir **https://fac-ja.shop** dans le navigateur.

Connexion admin par défaut (si seeder exécuté) :
- **Email** : `admin@jabir.com`
- **Mot de passe** : `secret123`

⚠️ **Changer le mot de passe immédiatement après la première connexion.**

---

## Dépannage fréquent

| Problème | Solution |
|----------|----------|
| Page blanche | `APP_DEBUG=true` temporairement + vérifier `storage/logs/laravel.log` |
| Erreur 500 | Vérifier permissions : `chmod -R 775 storage bootstrap/cache` |
| Images/logos absents | Relancer `php artisan storage:link` |
| Email ne fonctionne pas | Vérifier les paramètres SMTP dans hPanel → Email |
| Migration échoue | Vérifier credentials MySQL dans `.env` |

---

## Permissions recommandées

```bash
chmod -R 755 /home/user/public_html
chmod -R 775 /home/user/public_html/storage
chmod -R 775 /home/user/public_html/bootstrap/cache
chown -R www-data:www-data /home/user/public_html
```

---

## Mise à jour du site après modification

```bash
cd /home/user/public_html
git pull                          # si utilisation Git
composer install --no-dev -o      # si nouvelles dépendances
php artisan migrate --force       # si nouvelles migrations
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
