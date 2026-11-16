# Grup Simex / Elgarom – platforme web

Depozitul conține două aplicații separate.

## GRUP SIMEX Industrial CRM (`simex/`)

Platformă internă de ofertare, calcul de costuri și CRM, plus website-ul public Grup Simex.

| | |
|---|---|
| Pachet de instalare cPanel | `livrare/simex-crm-0.1.5-cpanel.zip` – dezarhivați și deschideți `install.php` |
| Instalare | [`docs/simex/DEPLOYMENT_CPANEL.md`](docs/simex/DEPLOYMENT_CPANEL.md) |
| Arhitectură | [`docs/simex/PROJECT_ARCHITECTURE.md`](docs/simex/PROJECT_ARCHITECTURE.md) |
| Starea modulelor | [`docs/simex/MODULES_ROADMAP.md`](docs/simex/MODULES_ROADMAP.md) |
| Motor de calcul și audit Excel | [`CALCULATION_ENGINE_SPEC`](docs/simex/CALCULATION_ENGINE_SPEC.md), [`EXCEL_IMPORT_SPEC`](docs/simex/EXCEL_IMPORT_SPEC.md), [`EXCEL_CALCULATION_AUDIT`](docs/simex/EXCEL_CALCULATION_AUDIT.md) |
| Bază de date, securitate | [`DATABASE_SCHEMA`](docs/simex/DATABASE_SCHEMA.md), [`SECURITY_CHECKLIST`](docs/simex/SECURITY_CHECKLIST.md) |

Fișierele Excel de calcul conțin tarife și nu fac parte din depozit; se încarcă din panou.

```bash
cd simex && composer install && npm install && cp .env.example .env && php artisan key:generate
php artisan migrate --seed && npm run build && php artisan serve
php artisan simex:admin admin@exemplu.ro   # cont Super Administrator
php artisan simex:modele /cale/catre/excel  # validează și activează modelele
php artisan test
```

## ELGAROM – site de prezentare (`platforma/`)

Site de prezentare și administrare pentru ELGAROM (zincare electrolitică slab acidă, Târgu Mureș, Argento Group).

| | |
|---|---|
| Pachet de instalare cPanel | [`livrare/elgarom-1.0.0-cpanel.zip`](livrare/) – dezarhivați și deschideți `install.php` |
| Instalare | [`docs/INSTALARE.md`](docs/INSTALARE.md) |
| Administrare și integrări | [`docs/ADMINISTRARE.md`](docs/ADMINISTRARE.md) |
| Actualizări semnate și recuperare | [`docs/ACTUALIZARI.md`](docs/ACTUALIZARI.md) |
| Raport de verificare și limitări | [`docs/RAPORT.md`](docs/RAPORT.md) |
| Inventar, migrare, decizii, progres | [`docs/PROIECT.md`](docs/PROIECT.md) |
| Cod sursă (Laravel 13, PHP 8.3) | [`platforma/`](platforma/) |
| Utilitar release/semnare | [`tools/release.php`](tools/release.php) |

## Backup-ul site-ului vechi (2026-11-06)

- `texte/` – textele paginilor vechi; `imagini-originale/` – biblioteca Media WordPress; `mirror/` – copia completă; `wp-api/` – export JSON.

## Dezvoltare locală

```bash
cd platforma && composer install && npm install && cp .env.example .env && php artisan key:generate
php artisan migrate --seed && npm run build && php artisan serve
php artisan elgarom:admin admin@exemplu.ro   # cont administrator (parolă temporară, 2FA la prima autentificare)
php artisan test
```
