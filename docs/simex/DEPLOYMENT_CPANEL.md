# Instalare pe cPanel

Pachet: `livrare/simex-crm-0.1.0-cpanel.zip` (conține `install.php`, codul, `vendor/` și resursele compilate).
Pe server nu sunt necesare Node.js, Composer, acces root sau procese permanente.

Instalarea din acest pachet a fost rulată cap-coadă pe MariaDB 10.11 cu PHP 8.3; nu a fost rulată pe serverul dumneavoastră.

## Cerințe

| Cerință | Minim | Unde se setează în cPanel |
|---|---|---|
| PHP | 8.3 | MultiPHP Manager / Select PHP Version |
| Extensii PHP | pdo_mysql, mbstring, openssl, sodium, zip, gd, fileinfo, ctype, tokenizer, xml, dom, simplexml, curl | Select PHP Version → Extensions |
| Bază de date | MySQL 8.0+ sau MariaDB 10.3+ | MySQL Databases |
| memory_limit | 256M (calculatorul de tablă folosește până la ~110 MB) | MultiPHP INI Editor |
| max_execution_time | 60 s (recomandat 120 s) | MultiPHP INI Editor |
| upload_max_filesize / post_max_size | 32M | MultiPHP INI Editor |
| HTTPS | certificat valid | SSL/TLS Status → AutoSSL |
| Cron | o sarcină la fiecare minut | Cron Jobs |
| Spațiu | ~150 MB pentru aplicație, plus backup-uri | – |

Instalatorul verifică aceste cerințe și le afișează înainte de a instala.

## Amplasare

**A. Recomandat – rădăcina domeniului pe `public/`.** Dezarhivați pachetul în afara `public_html`, de exemplu în
`/home/UTILIZATOR/simex`, apoi în cPanel → Domains setați Document Root pe `/home/UTILIZATOR/simex/public`.
Codul, `.env` și fișierele private rămân în afara directorului servit.

**B. Simplu – totul în `public_html`.** Dezarhivați pachetul direct în `public_html`. Fișierul `.htaccess` din rădăcină
trimite cererile către `public/` și blochează accesul la `.env`, `vendor/`, `storage/` și cod. Varianta A este mai sigură.

## Pași

1. **Baza de date.** cPanel → MySQL Databases: creați o bază de date și un utilizator cu parolă puternică, apoi dați-i „ALL PRIVILEGES” pe acea bază.
2. **Fișierele.** cPanel → File Manager: urcați `simex-crm-0.1.0-cpanel.zip` în directorul ales și alegeți Extract.
3. **Instalatorul.** Deschideți `https://domeniul-dvs.ro/install.php`.
4. **Cheia de instalare.** În File Manager deschideți `storage/app/private/install-key.txt` și copiați cheia în formular. Ea împiedică pe altcineva să instaleze platforma înaintea dumneavoastră.
5. **Formularul.** Adresa site-ului, datele bazei de date, numele, emailul și parola primului Super Administrator (minimum 12 caractere, cu litere mari, mici și cifre).
6. **Instalarea.** Instalatorul creează `.env` (cheie de criptare nouă, depanare oprită), tabelele, datele de referință fără prețuri, contul, cache-urile, apoi scrie `install.lock` și se șterge singur.
7. **Cron (obligatoriu).** cPanel → Cron Jobs → „Once Per Minute”. Instalatorul afișează comanda exactă pentru serverul dumneavoastră, de forma:
   ```
   * * * * * /usr/local/bin/php /home/UTILIZATOR/simex/artisan schedule:run >/dev/null 2>&1
   ```
8. **Prima autentificare.** `https://domeniul-dvs.ro/crm`: email și parolă, apoi scanați codul QR cu o aplicație de autentificare și salvați codurile de recuperare.

## După instalare, în această ordine

1. **Calcul de costuri → Modele Excel.** Încărcați pe rând cele cinci fișiere Excel, fiecare la calculatorul lui. După mesajul „Model validat … 0 diferențe”, apăsați „Activează”.
2. **Administrare → Companiile grupului.** Completați datele juridice ale fiecărui emitent, încărcați siglele GRUP SIMEX și FEPO, apoi confirmați datele. Fără confirmare, compania nu poate emite oferte, iar datele ei nu apar pe website.
3. **Bibliotecă → Utilaje.** Introduceți tarifele orare. Până atunci calculatoarele afișează doar ore.
4. **Administrare → Configurări.** Curs, cotă TVA, valabilitate, praguri de marjă și discount.
5. **Administrare → SMTP și email.** Configurați și trimiteți emailul de test.
6. **Administrare → Website public.** Textele, emailul de contact, politica de confidențialitate; bifați indexarea când totul este confirmat.
7. **Administrare → Utilizatori.** Creați conturile, cu rolul potrivit fiecăruia.
8. **Administrare → Backup.** Faceți primul backup.

## Ce face cron-ul

| Sarcină | Frecvență |
|---|---|
| trimite și reîncearcă emailurile din coadă (`simex:mail`) | la fiecare minut |
| backup complet verificat (`simex:backup`), se păstrează ultimele 10 | zilnic, 02:40 |
| retenție: fișiere temporare, jurnal de audit peste termen (`simex:prune`) | zilnic, 03:10 |

## Actualizări

Administrare → Actualizare aplicație: se încarcă un ZIP semnat (`manifest.json` + `manifest.sig` + `files/`).
Platforma verifică semnătura Ed25519 cu cheia publică din `config/simex.php`, hash-ul fiecărui fișier, versiunea și căile,
face backup, aplică fișierele și migrațiile, apoi verifică funcționarea. Un pachet nesemnat sau modificat este respins.
Datele (clienți, oferte, parametri, documente, modele Excel, setări) nu sunt atinse: `storage/` și `.env` nu pot fi suprascrise.

Pachetele se construiesc cu `php tools/release.php update --app=simex …` și cheia privată `simex-release-2026`,
care nu se află în depozit și nu se pune niciodată pe server. Păstrați-o într-un loc sigur: fără ea nu se pot semna actualizări.

## Recuperare

- Acces pierdut: `php artisan simex:admin email@domeniu.ro` creează sau resetează un Super Administrator (parolă nouă, 2FA resetat).
- Actualizare eșuată: `recover.php` din rădăcină restaurează un backup independent de aplicație; procedura este descrisă în `docs/ACTUALIZARI.md`.
- Mutare pe alt server: backup complet din panou, instalare nouă, apoi Backup → „Încarcă un backup” și restaurare.

## Dacă hostingul nu oferă ceva

| Lipsește | Efect | Alternativă |
|---|---|---|
| Cron | fără reîncercarea emailurilor și fără backup zilnic | reîncercare la vizitele în platformă; backup manual |
| Schimbarea Document Root | – | varianta B |
| Upload peste 32 MB | pachetul de actualizare nu se poate urca din browser | se urcă prin File Manager în `storage/app/private/updates/incoming/` |
| Linie de comandă | nu se pot rula comenzile `artisan` | toate operațiile curente există și în panou |
