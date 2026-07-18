<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Facturation & Stock (Laravel 13)

Application complète et sécurisée de facturation, de gestion de stock et de profil d'entreprise, dotée d'une interface mobile-first, de la génération de PDF, de l'envoi d'emails et du partage via WhatsApp.

## Fonctionnalités principales

- **Authentification & Sécurité en ligne** :
  - Protection complète de l'application par un middleware d'authentification (`auth`).
  - Système de connexion, d'inscription administrateur et de déconnexion sécurisé.
- **Profil de la Société émettrice & Logo** :
  - Personnalisation complète via le menu **Paramètres** : Nom de la société, numéro fiscal (SIRET/TVA/ICE), adresse, téléphone, email.
  - **Upload de Logo** avec affichage dynamique dans l'en-tête du site, sur les factures PDF et dans les emails envoyés.
- **Gestion des produits & stock** : Création avec quantité initiale, seuil d'alerte de stock faible, et **upload de photos de produits** avec affichage dans les listes et les factures.
- **Gestion des fournisseurs** : Liaisons avec les produits et suivi de leurs coordonnées.
- **Factures avec décrément automatique** :
  - Création de facture -> **décrémentation automatique** du stock et enregistrement d'un mouvement de sortie.
  - Modification de facture -> **recalcul automatique** en toute sécurité dans une transaction SQL (restauration puis application des nouvelles quantités).
  - Suppression de facture -> **restauration automatique** du stock initial.
- **Historique des mouvements de stock** : Traçabilité automatique et complète des entrées et sorties (`stock_movements`).
- **Interface Mobile-First** : Design responsive en Blade pur sans dépendances lourdes ; les tableaux se transforment en cartes tactiles ergonomiques sur smartphone.
- **Partage & Export** :
  - **WhatsApp** : Bouton de partage instantané ouvrant un lien `wa.me` prérempli avec un message récapitulatif.
  - **Email** : Envoi direct de la facture avec pièce jointe PDF en un clic.
  - **PDF** : Téléchargement et impression de factures professionnelles à l'effigie de l'entreprise.

## Installation & Démarrage rapide

### 1. Prérequis
- PHP >= 8.2
- Composer >= 2.x

### 2. Configuration & Migrations
```bash
composer install
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
php artisan migrate --graceful
php artisan storage:link
```

### 3. Lancer l'application
```bash
php artisan serve
```
Ouvrez votre navigateur sur `http://127.0.0.1:8000`. Vous serez invité à **créer un compte administrateur** ou à **vous connecter** pour accéder à votre espace sécurisé.

## Architecture & Base de données

- **Models** :
  - `User` (Administrateurs / Authentification)
  - `CompanySetting` (Configuration de la société émettrice : nom, logo, coordonnées)
  - `Product` (Stock, prix, seuil d'alerte, fournisseur)
  - `Supplier` (Coordonnées des fournisseurs)
  - `Invoice` & `InvoiceItem` (Facturation client et lignes d'achats)
  - `StockMovement` (Journalisation des flux d'inventaire)

## Tests automatisés
La suite de tests vérifie l'intégrité de l'authentification, de l'upload du logo, de la gestion des images de produits et du calcul / décrément automatique du stock :
```bash
php artisan test
```

## Utilisation des photos de produits

- **Ajouter une photo lors de la création d'un produit** :
  1. Accédez à **Produits** → **Créer un produit**
  2. Remplissez le formulaire et sélectionnez une image (PNG, JPG, WEBP - max 2 Mo)
  3. Enregistrez : l'image est stockée dans `storage/public/products/`

- **Modifier la photo d'un produit existant** :
  1. Accédez à **Produits** → Cliquez sur un produit
  2. Pour remplacer : sélectionnez une nouvelle image
  3. Pour supprimer : cochez "Supprimer cette photo"
  4. Enregistrez les modifications

- **Affichage des photos** :
  - Liste des produits : cartes avec aperçu de l'image (desktop + mobile)
  - Factures : colonne "Photo" dans le tableau avec vignette (max 50×50px)
  - PDF de facture : images intégrées directement si présentes
