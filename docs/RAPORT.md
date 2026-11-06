# Raport de livrare – ELGAROM 1.0.0 și actualizarea 1.1.0

Data: 2026-11-06. Pachet: `livrare/elgarom-1.0.0-cpanel.zip`. Amprenta SHA-256 se află în `livrare/SHA256SUMS`.

## Actualizarea 1.1.0 – site în patru limbi

Pachet: `livrare/elgarom-update-1.1.0.zip` (semnat cu `elgarom-release-2026`, se instalează peste 1.0.0 din Administrare → Actualizări). Lista de fișiere a versiunii: `livrare/files-1.1.0.json`. Modificările sunt descrise în `docs/CHANGELOG-1.1.0.md`.

**Ce s-a verificat pe site-ul de test (Apache, instalare 1.0.0 cu date):**
- Pachetul a fost încărcat din panou. Semnătura și toate fișierele au fost validate, apoi instalarea a durat 11 secunde (backup automat înainte).
- După instalare: `/`, `/de`, `/en`, `/hu`, paginile de servicii, ofertă, contact, date tehnice și `sitemap.xml` răspund 200.
- Conținutul în română a rămas identic: s-au comparat 6 pagini înainte și după, plus toate cele 19 adrese RO din sitemap între codul 1.0.0 și 1.1.0, pe aceeași bază de date. Singura diferență este eticheta nouă „Limba site-ului” a comutatorului.
- Comutatorul de limbă duce la pagina corespunzătoare (DE „Über uns” → HU „Rólunk”). hreflang: ro, de, en, hu, x-default.
- O cerere de ofertă trimisă din pagina maghiară primește numărul pe ecran în maghiară. Emailul de confirmare pentru client are subiectul în maghiară, iar notificarea internă rămâne în română.
- Administrare: tab-uri de limbă (13 pagini pe fiecare limbă, 4 servicii), panoul „Alte limbi”, avertismentul „Traduceri de verificat” pe panoul principal. Dezactivarea maghiarei din Limbi face ca `/hu` să răspundă 404, iar reactivarea îl readuce (200).
- Pe pagini în germană, engleză și maghiară nu a rămas text în română, nici în textele alternative ale imaginilor (scanare automată a celor 68 de adrese din sitemap).
- Valorile tehnice (1900 × 1200 × 450 mm, 100 kg) sunt copiate, nu reformulate. Valorile neconfirmate nu apar în nicio limbă.
- `php artisan test`: **31 de teste, 259 de verificări, toate trec** (9 teste noi pentru limbi și backup).

**Probleme găsite și reparate în timpul testelor:**
- Meniul cu texte mai lungi (DE/HU) se rupea pe două rânduri pe desktop. Varianta mobilă pornește acum sub 1320 px, iar comutatorul stă pe același rând.
- Datele tehnice din primul ecran se suprapuneau peste ilustrația 3D, inclusiv în română. Lista are acum lățimea coloanei de text.
- Backup: tabelele de sesiuni și cache lipseau din `database.sql`, așa că un import manual într-o bază goală (phpMyAdmin) dădea eroarea 500. Dump-ul conține acum structura lor, fără date. Am completat și instrucțiunile de recuperare manuală din `docs/ACTUALIZARI.md`.

**De făcut de dumneavoastră:** traducerile în germană, engleză și maghiară sunt automate. Rugați vorbitori nativi să le verifice; politicile de confidențialitate și cookies cer verificare juridică. După verificare, debifați avertismentul din Administrare → Limbi.

## Ce conține livrarea

| Element | Locație |
|---|---|
| Codul sursă (Laravel 13, PHP 8.3) | `platforma/` |
| Pachetul inițial pentru cPanel, cu `install.php` | `livrare/elgarom-1.0.0-cpanel.zip` |
| Lista de fișiere a versiunii (necesară pentru următoarea actualizare) | `livrare/files-1.0.0.json` |
| Utilitarul de release și semnare | `tools/release.php` |
| Recuperare fără panou | `platforma/recover.php` (inclus în pachet) |
| Instalare | `docs/INSTALARE.md` |
| Administrare și integrări | `docs/ADMINISTRARE.md` |
| Actualizări, semnare, recuperare | `docs/ACTUALIZARI.md` |
| Inventar, migrare, decizii | `docs/PROIECT.md` |
| Direcția vizuală | `docs/DIRECTIE-VIZUALA.md` |
| Copia site-ului vechi | `texte/`, `imagini-originale/`, `mirror/`, `wp-api/` |
| Cheia privată de semnare | transmisă separat, **nu** este în repository |

## Verificări executate

Mediu de test: Apache 2.4 cu `mod_rewrite` și `.htaccess`, PHP 8.3.6 (mod_php), MariaDB 10.11. Pachetul a fost dezarhivat direct în rădăcina web (varianta „public_html”). Browserul de test a fost Chromium (Playwright).

### Instalare
- Pachetul fresh se dezarhivează, iar `/` redirecționează la `install.php`. O cheie de instalare greșită este respinsă.
- Instalarea creează `.env` (`APP_DEBUG=false`), tabelele, conținutul și administratorul, apoi se șterge singură.
- Verificările HTTP de după instalare: `/health` 200, `/.env` 403, `/storage/logs/…` 403, `/vendor/…` 403.
- După instalare, `/install.php` și `/public/install.php` răspund 404. Directoarele aplicației răspund 403.

### Site public
- 13 pagini, detaliile serviciilor, `sitemap.xml` și `robots.txt` răspund 200.
- Redirect-uri 301 din site-ul vechi (`/despre-noi`, `/tehnologie`, `/?p=…`, `/?page_id=…`). URL-urile WordPress `/wp-login.php`, `/wp-admin`, `/xmlrpc.php` răspund 410. Paginile inexistente răspund 404.
- Antete: CSP cu nonce și `strict-dynamic`, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`. HSTS se activează automat pe HTTPS.
- Pe mobil (iPhone 13, emulat) și desktop (1440 px): fără erori în consolă.
- Scena 3D: am capturat cadre la 0 %, 25 %, 42 %, 60 %, 80 % și 95 % din derulare. Secvența piesă brută → coborâre → scufundare → piesă zincată pasivată funcționează. Varianta statică (fără WebGL sau cu mișcare redusă) afișează imaginea generată din scenă și etapele ca listă.

### Formular de ofertă
- Fără JavaScript, la un câmp obligatoriu lipsă: datele completate rămân, PDF-ul valid se păstrează pe server, iar un „PDF” care conținea cod PHP este respins. La a doua trimitere, cererea se salvează cu fișierul păstrat.
- Cu JavaScript, pe mobil: formularul în 5 pași blochează avansul fără descriere și arată revizuirea înainte de trimitere. Numărul cererii (`ELG-2026-00002`) apare pe ecran.
- Fișierele cererilor sunt stocate în `storage/app/private`. Fără autentificare, linkul de descărcare duce la pagina de login. Cu autentificare se descarcă, iar descărcarea apare în jurnalul de audit.
- Anti-spam: câmpul ascuns, timpul minim de completare și tokenul expirat blochează trimiterea, fără să se salveze nimic. Există și limitare pe IP.

### Email (SMTP)
- Cu SMTP oprit, cererea se salvează și confirmarea se afișează. Emailurile rămân „în așteptare”, cu eroarea înregistrată și reîncercare programată.
- După pornirea unui server SMTP local, reîncercarea a trimis notificarea către echipă și confirmarea către client.
- Testul a scos la iveală două erori, ambele corectate: oprirea reîncercării după primul email eșuat și o directivă Blade lipită de un cuvânt în emailul de confirmare.

### Administrare, 2FA și permisiuni
- Parolă greșită și cod 2FA greșit: respinse. Paginile de administrare redirecționează la configurarea 2FA până la activare. Se generează 10 coduri de recuperare.
- Toate cele 29 de pagini din meniu răspund 200, fără erori JavaScript.
- Teste automate: editorul primește 403 la utilizatori, actualizări și SMTP, iar rolul de vânzări primește 403 la pagini. Utilizatorul fără 2FA, sau cu 2FA neverificat în sesiune, nu are acces.

### Actualizări ZIP (test complet în browser)
- Pachet modificat după semnare: respins („Fișier modificat după semnare: routes/web.php”).
- Pachet nesemnat: respins. Pachet semnat cu altă cheie: respins.
- Actualizarea validă 1.0.0 → 1.0.1, cu o migrație nouă, s-a instalat după confirmare cu parolă și un cod 2FA nou: backup verificat, staging, migrații, verificare `/health`. Versiunea afișată a devenit 1.0.1.
- Actualizarea 1.0.1 → 1.0.2, cu o migrație care eșuează intenționat: fișierele și baza de date au fost restaurate automat. Tabela creată parțial a dispărut, versiunea a rămas 1.0.1, mentenanța s-a oprit, iar site-ul răspunde 200.
- Datele s-au păstrat după actualizări: cereri, fișiere, utilizatori, setări.
- O primă încercare a fost oprită corect în staging: verificarea de sintaxă respingea un polyfill din `vendor/` scris pentru PHP 8.5. Am limitat verificarea la codul propriu.
- Teste unitare: căi cu `..`, link-uri simbolice, căi protejate (`.env`, `storage/`, `public/media/`), versiuni, PHP minim, dimensiune.

### Backup și recuperare
- `elgarom:backup` creează și verifică arhiva.
- Restaurarea a eliminat o înregistrare adăugată după backup și a păstrat cererile și fișierele. Înainte s-a creat automat un backup de siguranță.
- `recover.php status | list | restore-db` funcționează fără framework, doar din linia de comandă.

### Consimțământ și Analytics (GA4 + Ads de test, mod direct)
- Înainte de alegere și cu „Doar necesare”: **0** cereri către Google și niciun cookie Google.
- După „Accept toate”: se încarcă `gtag.js`, iar consimțământul devine „granted”.
- dataLayer conține doar evenimente cu parametri din lista permisă (de exemplu `quote_step: {step: "1"}`), fără email, nume sau telefon.

### Asistent AI
- Testat prin SDK-ul oficial Anthropic pentru PHP (v1.6), cu un transport HTTP simulat. Cererea conține:
  - promptul de sistem cu `cache_control`;
  - instrumentul `propose_quote_prefill` cu `strict: true` și `tool_choice: auto`;
  - `output_config.effort: low`;
  - `fallbacks: "default"` cu antetul beta `server-side-fallback-2026-07-01`.
- Istoricul se retrimite nemodificat, inclusiv blocurile `thinking` cu semnătură. `tool_result` urmează imediat după `tool_use`.
- Costul se calculează din tokenii raportați de API.
- Fără cheie sau cu bugetul atins, răspunsul este 503, cu trimitere la formular și la telefon.

### Teste automate
`php artisan test`: **22 teste, 96 de verificări, toate trec**.

### Performanță (Lighthouse 12, măsurat pe site-ul instalat)

| Pagină | Profil | Performanță | Accesibilitate | Bune practici | SEO | LCP | CLS |
|---|---|---|---|---|---|---|---|
| Acasă (varianta statică: fără WebGL sau mișcare redusă) | mobil | 95 | 100 | 100 | 100 | 2,6 s | 0,006 |
| Acasă (varianta statică) | desktop | 100 | 100 | 100 | 100 | 0,6 s | 0,012 |
| Solicită ofertă | mobil | 98 | 100 | 100 | 100 | 2,1 s | 0,024 |
| Solicită ofertă | desktop | 100 | 100 | 100 | 100 | 0,4 s | 0,002 |
| Capacități tehnice | mobil | 95 | 100 | 100 | 100 | 2,4 s | 0,063 |
| Procesul de zincare | mobil | 95 | 100 | 100 | 100 | 2,6 s | 0,003 |

Profilul mobil al Lighthouse simulează o conexiune 4G lentă și un procesor încetinit de 4 ori. Măsurătorile au găsit două probleme, corectate înainte de valorile de mai sus: CLS 0,51 pe formular și CLS 0,44 pe pagina principală în varianta statică.

**Acasă cu scena 3D:** Lighthouse nu a putut finaliza măsurarea în acest mediu. Aici WebGL rulează în software (SwiftShader), iar Lighthouse nu ajunge la starea de repaus a procesorului. Am măsurat separat, cu Playwright (desktop, 1440 px):
- FCP și LCP: 232 ms; CLS: 0,012;
- scena devine activă la ~1,9 s, după ce pagina e deja interactivă;
- Three.js (566 KB necomprimat, ~145 KB gzip) și GSAP (~44 KB gzip) se încarcă doar pe Acasă, după afișare.

Consumul scenei este limitat:
- rată maximă cât vizitatorul derulează;
- 15 cadre pe secundă după 3 secunde fără derulare;
- oprire completă după 20 de secunde fără derulare;
- oprire când scena nu e vizibilă sau fila e ascunsă;
- 30 de cadre pe secundă și rezoluție redusă pe telefoane.

Pe dispozitive reale cu GPU, valorile trebuie confirmate după publicare, cu PageSpeed Insights.

## Limitări rămase și informații necesare

1. **Informații lipsă din materialele existente** (lista „De completat” din administrare). Nu am inventat nimic și nimic din listă nu este publicat:
   - denumirea juridică, CUI, Nr. Reg. Com., cod poștal, program;
   - certificări și documente;
   - grosimi de strat, tipuri de pasivare, rezistență în ceață salină;
   - dimensiunile băilor, greutatea maximă pe dispozitiv, dimensiunea maximă a pieselor în tambur, capacitatea de producție;
   - industriile deservite (exemplele sunt nepublicate) și proiectele de referință;
   - descrierea Argento Group;
   - confirmarea pentru oțelurile inoxidabile și înalt aliate, preluate din formularul vechi.
2. **Integrări netestate cu conturi reale:** API-ul Claude (fără cheie), Google OAuth, Search Console, GA4 Data API, Business Profile și Google Ads (fără conturi și client OAuth). Codul tratează stările Neconfigurat, Conectat, Autorizare expirată și Acces necesar, dar trebuie verificat la prima conectare reală.
3. **Google Business Profile:** recenziile și postările nu sunt implementate, pentru că API-urile cer aprobare de la Google. Panoul trimite la business.google.com.
4. **Google Ads:** doar citire de rapoarte, care cere un developer token aprobat. Platforma nu modifică niciodată bugete și nu publică niciodată campanii.
5. **Alte limbi:** începând cu 1.1.0 site-ul este în română, germană, engleză și maghiară (vezi secțiunea de mai sus). Traducerile sunt automate și trebuie verificate de vorbitori nativi; politicile legale cer verificare juridică.
6. **Hosting:** testat pe Apache 2.4. Pe LiteSpeed (des întâlnit la cPanel), `.htaccess` folosește doar directive compatibile, dar nu a fost testat efectiv.
7. **Imagini pierdute la copierea site-ului vechi:** 10 originale au fost descărcate incomplet. 7 au fost recuperate din variante redimensionate intacte, iar 3 s-au pierdut (`7a1b32c5…`, `pic-1.jpg`, `19fbcffd….png`).
8. **Cheia de semnare:** a fost generată în sesiunea de dezvoltare și v-a fost transmisă separat. Recomand să generați propria pereche (`php tools/release.php keygen`) și să o rotiți prin prima actualizare semnată.
9. **Rollback:** se face automat pentru fișiere și migrații, din backup-ul făcut chiar înainte de actualizare. Pentru modificări ireversibile sau externe, restaurarea se face din backup. Nu există o promisiune de rollback universal.
