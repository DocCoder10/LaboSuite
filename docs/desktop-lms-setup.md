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
  - Category selection
  - Dynamic result field generation
  - Save analysis
  - Report preview
  - Print/PDF via Chromium print dialog
- Config pages:
  - Catalog management (create entries, delete parameter)
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
1. Ensure SQLite file exists
2. Run `php artisan migrate --force --seed`
3. Start Laravel local server
4. Open desktop window

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

Generated installer: `app/dist/LaboSuite-LMS-Setup-<version>.exe`

## Notes
- This repo now has a production-oriented scaffold, but not yet full admin CRUD edit/delete for all catalog entities.
- PDF export relies on Chromium print-to-PDF from the report page.
