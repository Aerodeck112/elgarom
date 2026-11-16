# Actualizări ZIP – creare, semnare, instalare, recuperare

## Principiu

Pe server se instalează doar pachetele **semnate** cu cheia privată Ed25519 a emitentului. Cheia publică de verificare face parte din cod (`config/elgarom.php` → `updates.public_keys`). Cheia privată **nu** se află pe server și **nu** intră în pachete.

Un pachet conține:
- `manifest.json`: versiunea, versiunea minimă de la care se poate actualiza, PHP minim, extensii, changelog, hash-ul SHA-256 al fiecărui fișier și lista de fișiere de șters;
- `manifest.sig`: semnătura detașată a manifestului;
- `files/…`: fișierele.

## Ce se verifică la upload (înainte de orice modificare)

1. Dimensiunea maximă (120 MB), numărul de fișiere și dimensiunea totală dezarhivată.
2. Fiecare cale: fără `..`, fără căi absolute, fără `\`, fără caractere de control. Link-urile simbolice sunt respinse, la fel arhivele cu raport de compresie suspect (arhive-bombă).
3. Semnătura manifestului, verificată cu cheile publice de încredere.
4. Aplicația și versiunile: pachetul trebuie să fie mai nou decât versiunea instalată și compatibil cu ea (`min_from`). Se verifică și PHP-ul și extensiile cerute.
5. Fiecare fișier din pachet trebuie declarat în manifest, cu hash-ul corect. Orice fișier în plus este respins.
6. Nicio cale nu poate atinge zonele protejate: `.env`, `storage/` (fișiere private, backup-uri, jurnale), `public/media/`, `bootstrap/cache/`, `install.lock`.

Respingerile verificate în teste:
- pachet modificat după semnare: „Fișier modificat după semnare: routes/web.php”;
- pachet nesemnat;
- pachet semnat cu o cheie necunoscută;
- versiune incompatibilă.

## Instalarea (Administrare → Actualizări)

1. Încărcați pachetul. Dacă e mai mare decât limita de upload, urcați-l prin File Manager în `storage/app/private/updates/incoming/` și alegeți-l din listă.
2. Pagina de revizuire arhivă changelog-ul, numărul de fișiere modificate, noi și șterse, cerințele și spațiul liber.
3. Confirmați cu parola și un cod 2FA **nou**.
4. Platforma execută automat:
   1. blocare exclusivă (o singură instalare simultan);
   2. **backup verificat** al bazei de date și al fiecărui fișier care va fi suprascris sau șters;
   3. dezarhivare în **staging**, recalcularea hash-urilor și verificarea sintaxei PHP pentru codul propriu;
   4. mod mentenanță, apoi înlocuirea fiecărui fișier prin scriere temporară și redenumire atomică;
   5. **migrațiile** bazei de date, urmărite în tabela `migrations`;
   6. **verificări de sănătate**: `/health` cu token (răspunde, baza de date funcționează, versiunea e cea nouă) și, dacă HTTP spre propriul domeniu e blocat, o verificare internă;
   7. la orice eroare: **restaurare automată** a fișierelor (cele noi se elimină) și a bazei de date, dacă migrațiile au pornit; apoi ieșirea din mentenanță.
5. Istoricul păstrează jurnalul complet al fiecărei instalări.

Conținutul, utilizatorii, cererile, conversațiile AI, setările, fișierele încărcate și `.env` nu sunt atinse. De la 1.1.1 nici fișierele de server `.htaccess`, `public/.htaccess`, `.user.ini` și `php.ini` nu sunt atinse.

### Pachetul potrivit

| Fișier | La ce folosește | Unde se pune |
|---|---|---|
| `elgarom-X.Y.Z-cpanel.zip` | instalare nouă, pe un server gol | File Manager → Extract, apoi `install.php` |
| `elgarom-update-X.Y.Z.zip` | actualizarea unui site deja instalat | Administrare → Actualizări |

Pachetul de instalare încărcat în Actualizări este respins, pentru că nu are semnătură și manifest. Folosiți fișierul care are `update` în nume.

### Eroarea la actualizare pe cPanel: site-ul trece pe alt PHP

cPanel păstrează versiunea PHP a domeniului într-un bloc din `.htaccess` („cPanel-generated handler”). Actualizarea 1.1.0 înlocuia acest fișier. Site-ul trecea pe PHP-ul implicit al serverului (adesea mai vechi de 8.3), verificarea de după instalare primea eroare și actualizarea se restaura singură. De la 1.1.1 `.htaccess` nu mai este atins.

Dacă site-ul afișează „Site-ul cere PHP 8.3 sau mai nou” sau mesajul Composer «Your Composer dependencies require a PHP version ">= 8.3.0"»:

| Unde apare | Cauza | Rezolvare |
|---|---|---|
| în browser | domeniul rulează pe PHP-ul implicit al serverului | cPanel → MultiPHP Manager → bifați domeniul → alegeți altă versiune → Apply → alegeți din nou 8.3 → Apply (cPanel rescrie blocul din `.htaccess`) |
| în emailurile cron-ului sau în Terminal | comanda `php` este PHP-ul implicit al serverului | folosiți calea completă: `/opt/cpanel/ea-php83/root/usr/bin/php` (CloudLinux: `/opt/alt/php83/usr/bin/php`) |

Dezarhivarea pachetului de instalare peste un site existent înlocuiește și ea `.htaccess`. Aplicați aceeași rezolvare din MultiPHP Manager.

### Server care blochează cererile către propriul domeniu

După instalare, platforma își cere pagina `/health`. Pe unele hostinguri cererea este blocată sau filtrată (firewall, WAF, CDN). De la 1.1.1, în acest caz verificarea se face din interiorul aplicației (versiunea fișierelor, baza de date, paginile publicate, drepturile de scriere), iar jurnalul arată motivul. Dacă site-ul răspunde însă cu o eroare de server (HTTP 500), actualizarea se restaurează automat, ca înainte. Mesajul din jurnal arată și începutul erorii.

**Limitare asumată:** rollback-ul automat restaurează baza de date din backup-ul făcut chiar înainte de actualizare. Pentru migrațiile ireversibile, restaurarea se face din acel backup. Datele scrise *în timpul* celor câteva secunde de mentenanță nu există, pentru că site-ul este în mentenanță. Nu există o promisiune de rollback universal pentru orice tip de modificare externă (de exemplu fișiere modificate manual pe server după actualizare).

## Recuperare dacă panoul nu mai pornește

Din cPanel → Terminal, sau cu o sarcină cron temporară dacă hostingul nu are Terminal:

```
cd /home/UTILIZATOR/elgarom
php recover.php status
php recover.php list
php recover.php restore-code elgarom-pre_actualizare-AAAAMMZZ-HHMMSS-xxxxxx.zip
php recover.php restore-db   elgarom-pre_actualizare-AAAAMMZZ-HHMMSS-xxxxxx.zip
php recover.php maintenance-off
```

- `recover.php` nu depinde de framework, rulează doar în linia de comandă (din web răspunde 404) și verifică hash-urile backup-ului înainte de restaurare.
- Ultima soluție: restaurați backup-ul complet zilnic cu `php artisan elgarom:restore <fișier>` sau din panou.
- Fără Terminal: arhiva de backup se poate deschide cu File Manager. Folderul `code/` conține fișierele vechi; copiați-le peste cele curente fără să ștergeți `.env`, `storage/` și `public/media/`. Dacă folderul `bootstrap/cache/` lipsește, creați-l gol. Goliți apoi `storage/framework/views/` (vederile compilate ale versiunii noi). `database.sql` se importă din phpMyAdmin. De la 1.1.0 dump-ul conține și structura tabelelor de sesiuni și cache, deci importul merge și într-o bază goală.

## Crearea și semnarea unei actualizări (pe calculatorul de dezvoltare)

Cerințe locale: PHP 8.3 cu extensiile sodium și zip, Composer, Node.js 20+.

```bash
# o singură dată: perechea de chei (dacă doriți propria cheie în locul celei livrate)
php tools/release.php keygen ~/chei-elgarom
# cheia publică afișată se adaugă în platforma/config/elgarom.php → updates.public_keys
# printr-o actualizare semnată cu cheia veche (rotația cheii), apoi cheia veche se poate elimina
```

```bash
# pachet complet pentru o instalare nouă
php tools/release.php package --version=1.0.0
```

```bash
# actualizare semnată 1.0.0 → 1.1.0
php tools/release.php update --version=1.1.0 --min-from=1.0.0 \
    --previous=dist/files-1.0.0.json \
    --key=~/chei-elgarom/elgarom-release-2026.secret \
    --changelog=CHANGELOG-1.1.0.md
```

```bash
# verificare independentă
php tools/release.php verify dist/elgarom-update-1.1.0.zip
```

Reguli:
- Păstrați fișierul `dist/files-<versiune>.json` al fiecărei versiuni publicate. Din el se calculează fișierele de șters la următoarea actualizare.
- Migrațiile noi se adaugă în `platforma/database/migrations/` și nu se modifică după publicare.
- Cheia privată se păstrează offline: manager de parole, stick criptat. Dacă este compromisă, publicați imediat o actualizare semnată care înlocuiește cheia publică.
- Cheia privată generată pentru versiunea 1.0.0 v-a fost transmisă separat, nu prin repository. Identificatorul ei este `elgarom-release-2026`.
