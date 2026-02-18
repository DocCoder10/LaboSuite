# LaboSuite LMS - Desktop Offline Setup

## Stack
- Backend: Laravel 12 + SQLite
- Frontend: Blade + Tailwind + Vite
- Desktop shell: Electron
- Packaging: electron-builder (NSIS target for Windows)

## Current Features Implemented
- Medical hierarchy catalog:
  - Discipline -> Category -> Subcategory -> Parameter
- Patient + analysis workflow:
  - New analysis
  - Patient details
  - Category selection (screen 1)
  - Dynamic result entry (screen 2, separated from selection)
  - Save analysis
  - Report preview
  - Print/PDF via Chromium print dialog (A4 enforced by print CSS)
- Config pages:
  - Catalog management (full CRUD + display ordering)
  - Lab identity + report layout settings
- Localization:
  - FR / EN / AR switch from UI

## Backend Local Run
```bash
cd backend
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

## Import Legacy Analyses (Python JSON)
Command:
```bash
cd backend
php artisan lms:import-legacy-analyses /absolute/path/to/analyses.json --wipe
```

Notes:
- `--wipe` deletes current catalog (disciplines/categories/parameters) before import.
- The importer maps legacy `categorie` to LMS discipline and each legacy analysis `nom` to an LMS category.
- Legacy `sous_analyses` are imported as parameters under that category.

Open: `http://127.0.0.1:8000`

## Electron Local Run
Prerequisites:
- PHP CLI available in `PATH` (or set env `LMS_PHP_BIN`)

```bash
cd app
npm install
npm run start
```

Electron will:
1. Ensure SQLite + Laravel storage paths exist
2. Run `php artisan migrate --force --seed`
3. Start Laravel local server
4. Open desktop window

Runtime data paths:
- Dev mode: `backend/database/database.sqlite` and `backend/storage`
- Packaged mode: Electron `userData` directory under `laravel/database/database.sqlite` and `laravel/storage`

## Windows Packaging (NSIS)
1. Build backend assets first:
```bash
cd backend
npm run build
```
2. Ensure backend vendor is installed:
```bash
cd backend
composer install --no-dev --optimize-autoloader
php artisan optimize
```
3. Add embedded PHP runtime binary:
- Put `php.exe` in: `app/runtime/php/php.exe`
4. Build installer:
```bash
cd app
npm run dist:win
```

Quick packaging validation (current OS target):
```bash
cd app
npm run pack
```

Generated installer: `app/dist/LaboSuite-LMS-Setup-<version>.exe`

## Notes
- This repo now has a production-oriented scaffold, but not yet full admin CRUD edit/delete for all catalog entities.
- PDF export relies on Chromium print-to-PDF from the report page.
