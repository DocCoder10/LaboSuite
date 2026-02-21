# LaboSuite LMS - Desktop Offline Setup

## Stack
- Backend: Laravel 12 + SQLite
- Frontend: Blade + Tailwind + Vite
- Desktop shell: Electron
- Packaging: electron-builder (NSIS target for Windows)

## Current Features Implemented
- Medical hierarchy catalog:
  - Official structure: Categorie (domain) -> Analyse -> Sous-analyse -> Sous-niveaux -> Valeur terminale
  - Maximum hierarchy depth: 5 levels
  - Leaf-only values: only terminal items can carry value/unit/type/reference
  - Duplicate guards:
    - no duplicate name on the same level
    - no child with the same name as its direct parent
  - Safe deletion with centered confirmation popup (deletion blocked when children exist)
  - Tree UX:
    - selected node + parent path highlighting by level
    - smooth open/close transitions
    - up to 3 root branches open simultaneously
    - per-node unsaved edit state preserved until save/cancel
- Patient + analysis workflow:
  - New analysis
  - Patient details
  - Category selection (screen 1)
  - Dynamic result entry (screen 2, separated from selection)
  - Save analysis
  - Report preview
  - Print/PDF via Chromium print dialog (A4 enforced by print CSS)
- Config pages:
  - Catalog management (single tree editor inspired by legacy Python UX)
  - Lab identity + report layout settings
- Localization:
  - French only (FR)

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

Linux (if window does not appear with Wayland):
```bash
cd app
ELECTRON_OZONE_PLATFORM_HINT=x11 npm run start
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

## UI Stability Guardrails (2026-02-21)
- Fixed: fields in `Nouvelle analyse` (`Prenom`, `Nom`, `Telephone`, `Age`) are editable again.
- Root cause: unresolved Blade component tags (`<x-ui.input ...>`) were rendered as raw HTML text when Blade directives were mixed directly inside component attributes.
- Safe pattern:
  - compute conditional attributes in `@php` first (example: `min/max/step/required`)
  - pass them as normal component attributes (`:min="$inputMin"`, etc.)
  - avoid `@if ... @endif` inside the opening tag of `<x-ui.*>` components.
- Added regression test: `backend/tests/Feature/BladeComponentCompilationTest.php`
  - ensures critical pages never return raw `<x-ui.*>` tags.
