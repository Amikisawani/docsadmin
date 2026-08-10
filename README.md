# AdminFlow — DocsAdmin

Plateforme de Gestion Électronique des Documents Administratifs (GED/DMS) :
cycle de vie documentaire, workflows de validation, publipostage, signature du
Directeur de Cabinet, archivage, journal d'audit et vérification publique par QR Code.

Le cahier des charges fonctionnel se trouve dans [`docs/cahier-des-charges.md`](docs/cahier-des-charges.md).

## Stack

| Couche | Technologies |
| --- | --- |
| Backend | PHP 8.3+, Laravel 13, Sanctum, Spatie Permission / Activitylog, DomPDF, PHPWord |
| Frontend | Vue 3 + TypeScript, Vite, Pinia, Vue Router, PrimeVue, Tailwind CSS |
| Données | PostgreSQL (production), SQLite (tests), Redis (cache/queues, optionnel) |

## Prérequis

- PHP 8.3 ou 8.4 avec les extensions `bcmath`, `gd`, `mbstring`, `pdo_sqlite`/`pdo_pgsql`, `xml`, `zip`
- Composer 2
- Node.js 20.19+ ou 22.12+ (contrainte du bundler Rolldown/Vite 8)

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm ci
npm run build
```

## Développement

```bash
composer dev     # serveur PHP + worker de queue + logs + Vite en parallèle
```

## Qualité

```bash
vendor/bin/pint          # style PHP (--test en vérification seule)
php artisan test         # suite PHPUnit (SQLite en mémoire)
npm run typecheck        # vérification TypeScript / Vue
npm run build            # build de production des assets
```

Ces quatre commandes sont exécutées par la CI GitHub Actions
([`.github/workflows/ci.yml`](.github/workflows/ci.yml)) sur chaque pull request.

## Sécurité

- L'API est versionnée sous `/api/v1` et protégée par Sanctum.
- La création de comptes (`POST /api/v1/auth/register`) et la gestion des
  utilisateurs (création, modification, suppression) sont réservées au rôle `admin`.
- Les endpoints d'authentification non protégés sont limités en débit
  (5 tentatives/minute par couple email + IP, 20/minute par IP).
- Seul le rôle `directeur_cabinet` peut signer un document ou une campagne.
