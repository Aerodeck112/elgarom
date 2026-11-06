# ELGAROM – platforma web

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
