# Instalare pe cPanel

Pachet: `livrare/elgarom-1.0.0-cpanel.zip` (conține `install.php`, codul, `vendor/` și resursele compilate). Pe server nu sunt necesare Node.js, Composer, acces root sau procese permanente.

## Cerințe

| Cerință | Minim | Unde se setează în cPanel |
|---|---|---|
| PHP | 8.3 (testat pe 8.3.6) | MultiPHP Manager / Select PHP Version |
| Extensii PHP | pdo_mysql, mbstring, openssl, sodium, zip, gd (cu WebP), fileinfo, ctype, tokenizer, xml, dom, curl | Select PHP Version → Extensions |
| Bază de date | MySQL 8.0+ sau MariaDB 10.3+ (testat pe MariaDB 10.11) | MySQL Databases |
| memory_limit | 128M (recomandat 256M) | MultiPHP INI Editor |
| max_execution_time | 60 s (recomandat 120 s, pentru backup și actualizări) | MultiPHP INI Editor |
| upload_max_filesize / post_max_size | 32M (pentru fișierele clienților și imagini) | MultiPHP INI Editor |
| HTTPS | certificat valid | SSL/TLS Status → AutoSSL |
| Cron | o sarcină la fiecare minut | Cron Jobs |
| Spațiu pe disc | ~150 MB pentru aplicație, plus backup-uri (~30 MB fiecare, cu imagini) | – |

Instalatorul verifică automat toate aceste cerințe și le afișează înainte de instalare.

## Variante de amplasare

**A. Recomandat – rădăcina domeniului pe `public/`.** Dezarhivați pachetul într-un director din afara `public_html`, de exemplu `/home/UTILIZATOR/elgarom`. Apoi, în cPanel → Domains, setați Document Root pentru domeniu pe `/home/UTILIZATOR/elgarom/public`. Codul, `.env` și fișierele private rămân în afara directorului web.

**B. Simplu – totul în `public_html`.** Dezarhivați pachetul direct în `public_html`. Fișierul `.htaccess` din rădăcină trimite cererile către `public/` și blochează (403) accesul la `.env`, `vendor/`, `storage/`, cod și backup-uri. Fiecare director sensibil are și propriul `.htaccess` „Require all denied”. Varianta a fost testată sub Apache 2.4 cu `mod_rewrite`. La final, instalatorul verifică prin HTTP că `/.env`, `/vendor/` și `/storage/` nu sunt accesibile.

## Pași

1. **Baza de date:** cPanel → MySQL Databases. Creați o bază de date și un utilizator cu parolă puternică, apoi adăugați utilizatorul la bază cu „ALL PRIVILEGES”.
2. **Fișierele:** cPanel → File Manager. Urcați `elgarom-1.0.0-cpanel.zip` în directorul ales, apoi alegeți Extract.
3. **Permisiuni:** directoarele trebuie să fie 755, iar fișierele 644. Pe hostingurile cPanel standard, File Manager le setează implicit.
4. **Instalatorul:** deschideți `https://domeniul-dvs.ro/`, care vă redirecționează automat la `install.php`.
5. **Cheia de instalare:** în File Manager, deschideți `storage/app/private/install-key.txt` și copiați cheia în formular. Cheia împiedică pe altcineva să instaleze site-ul înaintea dumneavoastră.
6. **Formularul:** completați adresa site-ului, datele bazei de date, administratorul și emailul pentru notificări. Parola trebuie să aibă minimum 12 caractere, cu litere mari, mici și cifre.
7. **Instalarea:** instalatorul face următoarele:
   - creează `.env` cu o cheie de criptare nouă și `APP_DEBUG=false`;
   - creează tabelele;
   - importă conținutul inițial (13 pagini, 4 servicii, 10 întrebări, 14 fotografii reale optimizate, redirect-urile din site-ul vechi);
   - creează administratorul;
   - generează cache-urile;
   - scrie `install.lock` și se șterge singur.
8. **Cron (obligatoriu):** cPanel → Cron Jobs → „Once Per Minute”:
   ```
   * * * * * /usr/local/bin/php /home/UTILIZATOR/elgarom/artisan schedule:run >/dev/null 2>&1
   ```
   Calea către PHP poate diferi, de exemplu `/opt/cpanel/ea-php83/root/usr/bin/php`. Instalatorul afișează comanda exactă pentru serverul dumneavoastră.
9. **Prima autentificare:** deschideți `https://domeniul-dvs.ro/admin` și introduceți emailul și parola. Scanați codul QR cu o aplicație de autentificare și salvați cele 10 coduri de recuperare.
10. **Configurări:** SMTP (cu test), lista „De completat”, primul backup. Opțional: Google și asistentul AI.

## Ce face cron-ul

| Sarcină | Frecvență |
|---|---|
| Trimite și reîncearcă emailurile din coadă (`elgarom:mail`) | la fiecare minut |
| Backup complet verificat (`elgarom:backup`); se păstrează ultimele 10 | zilnic, 02:40 |
| Retenție: conversațiile AI peste termen, fișierele temporare, jurnalul de audit mai vechi de 365 de zile (`elgarom:prune`) | zilnic, 03:10 |

Fără cron, site-ul funcționează, dar emailurile eșuate se reîncearcă doar când cineva intră în administrare, iar backup-urile automate nu rulează.

## Facilități care pot lipsi pe hosting și alternativa

| Lipsește | Efect | Alternativă |
|---|---|---|
| Cron | fără reîncercare automată, fără backup zilnic | reîncercare la vizitele în admin; backup manual din panou |
| `imagewebp` în GD | imaginile se servesc doar JPEG/PNG | se activează WebP la furnizor; site-ul funcționează |
| HTTP către propriul domeniu (loopback) | verificarea de sănătate după actualizare nu poate face apel HTTP | se face automat o verificare internă (baza de date și conținutul) |
| Upload > 32 MB | pachetele de actualizare nu se pot urca din browser | se urcă prin File Manager în `storage/app/private/updates/incoming/` și se aleg din listă |
| Schimbarea Document Root | – | varianta B (`.htaccess`) |
| LiteSpeed în loc de Apache | `.htaccess` compatibil (fără directive specifice Apache) | – |

## Mutarea pe alt server

1. În panou, creați un backup complet și descărcați-l.
2. Instalați pachetul pe serverul nou, apoi urcați backup-ul din Backup → „Încarcă un backup” și restaurați-l.
3. Cheile secrete (SMTP, AI, Google, 2FA) sunt recriptate automat cu cheia noii instalări.
