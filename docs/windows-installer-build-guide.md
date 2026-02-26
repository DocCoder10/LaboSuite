# LaboSuite LMS - Guide Complet pour Generer le Setup Windows (.exe)

Ce guide explique comment generer l'installateur Windows final de LaboSuite LMS depuis GitHub, sur un PC Windows.

## 1) Objectif

Produire le fichier installateur officiel:

`app\dist\LaboSuite-LMS-Setup-1.0.0.exe`

## 2) Prerequis (machine Windows de build)

Installe ces outils sur Windows 10/11 x64:

- Git
- Node.js LTS (recommande: v20)
- PHP CLI (recommande: 8.2 ou 8.3)
- Composer

Verifie dans un terminal PowerShell:

```powershell
git --version
node -v
npm -v
php -v
composer -V
```

## 3) Cloner le projet depuis GitHub

```powershell
cd C:\
git clone https://github.com/TON_COMPTE/TON_REPO.git
cd TON_REPO
```

## 4) Build Backend Laravel

```powershell
cd backend
npm install
composer install --no-dev --optimize-autoloader
npm run build
php artisan optimize
```

## 5) Ajouter le runtime PHP embarque (obligatoire)

Le setup embarque son propre PHP pour l'execution offline.  
Tu dois mettre un runtime PHP Windows dans:

`app\runtime\php\`

Le minimum attendu:

- `app\runtime\php\php.exe`
- `app\runtime\php\ext\...`
- les DLL PHP associees au runtime

Important:

- Ne copie pas seulement `php.exe`.
- Copie le dossier complet du runtime PHP (version NTS x64 recommandee).

Option recommandee:

- utiliser un build PHP Zip NTS x64 (non-thread-safe), puis extraire tout son contenu dans `app\runtime\php\`.

Verification rapide:

```powershell
cd ..\app
dir .\runtime\php\php.exe
```

## 6) Generer le Setup Windows final

Toujours depuis `app`:

```powershell
npm install
npm run dist:win
```

Si tout se passe bien, le setup est cree dans `app\dist`.

## 7) Ou trouver le fichier final a partager

Chemin attendu:

`app\dist\LaboSuite-LMS-Setup-1.0.0.exe`

Tu peux ensuite:

- partager directement le `.exe`
- ou le compresser en `.zip` avant envoi

Exemple zip PowerShell:

```powershell
Compress-Archive -Path .\dist\LaboSuite-LMS-Setup-1.0.0.exe -DestinationPath .\dist\LaboSuite-LMS-Setup-1.0.0.zip -Force
```

## 8) Test rapide avant envoi

Sur un PC Windows de test:

1. Lance le `Setup.exe`.
2. Verifie la page de configuration (Nom structure, Adresse, etc.).
3. Clique `Finish`.
4. Verifie:
   - raccourci Desktop cree automatiquement
   - lancement auto de l'app apres `Finish`
5. Dans l'app, confirme que les infos saisies (identite + logo) sont bien appliquees.

## 9) Erreurs frequentes et solutions

### Erreur: `Embedded PHP runtime not found`

Cause:

- `app\runtime\php\php.exe` absent

Solution:

- mettre le runtime PHP Windows complet dans `app\runtime\php\`

### Erreur `composer install` (extensions PHP manquantes)

Cause:

- PHP local de build sans extensions requises

Solution:

- activer/installer les extensions necessaires pour Laravel (ex: `mbstring`, `openssl`, `pdo_sqlite`, `sqlite3`, `fileinfo`)

### Le setup n'apparait pas dans `dist`

Cause possible:

- build interrompu avant la phase NSIS

Solution:

- relancer `npm run dist:win` depuis Windows
- lire la fin du log pour identifier la dependance manquante

## 10) Sequence complete (copier-coller direct)

Adapte seulement l'URL GitHub:

```powershell
cd C:\
git clone https://github.com/TON_COMPTE/TON_REPO.git
cd TON_REPO

cd backend
npm install
composer install --no-dev --optimize-autoloader
npm run build
php artisan optimize

cd ..\app
# IMPORTANT: placer le runtime PHP complet dans app\runtime\php avant la commande suivante
npm install
npm run dist:win

dir .\dist\LaboSuite-LMS-Setup-*.exe
```

## 11) Resultat attendu

Le fichier final a distribuer est:

`app\dist\LaboSuite-LMS-Setup-1.0.0.exe`

