# PIOS

PIOS is a Laravel application for the Pak ITAM Online Supermarket platform. The app uses FilamentPHP 5 for the authenticated admin/back-office UI and MySQL for persistence.

## Stack

- PHP 8.4
- Laravel 13
- FilamentPHP 5
- MySQL
- Vite and Tailwind CSS 4

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS pios_my CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
npm run build
```

Create a local Filament user:

```bash
php artisan make:filament-user
```

Run the app:

```bash
composer run dev
```

Open the Filament panel at:

```text
http://localhost:8000/admin
```

## Verification

```bash
php artisan test
npm run build
```

## Product Context

- [PIOS_PRD.md](PIOS_PRD.md) is the product requirements source of truth.
- [CLAUDE.md](CLAUDE.md) captures implementation guidance for coding agents.
