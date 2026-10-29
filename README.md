# BE-Market Backend

API REST Laravel pour la plateforme e-commerce BE-Market.

## Stack

- Laravel 13.x
- PHP 8.3+
- PostgreSQL
- Sanctum (authentification API)
- Tailwind CSS 4.x (via Vite)

## Structure

```
be-market-back/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/          # Controllers API publiques et utilisateur
│   │   └── Admin/        # Controllers administration
│   ├── Models/
│   ├── Services/
│   └── Support/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── api.php
│   └── web.php
└── config/
```

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
```

## Scripts disponibles

```bash
# Installer et preparer l'environnement
composer run setup

# Lancer le serveur de developpement
composer run dev

# Linter
composer run pint

# Tests
composer run test
```

## Configuration

Variables d'environnement essentielles :

- `APP_URL` - URL de l'application
- `FRONTEND_URL` - URL du frontend (pour les liens de reset password)
- `DB_CONNECTION` / `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD`
- `CORS_ALLOWED_ORIGINS` - Origines autorisées pour les requêtes CORS

## Authentification

L'API utilise Laravel Sanctum avec des cookies de session. Le frontend doit :
1. Appeler `/sanctum/csrf-cookie` avant login
2. Envoyer les cookies avec chaque requête (`credentials: 'include'`)

## Licence

MIT
