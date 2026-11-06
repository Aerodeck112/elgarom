# ELGAROM – document de proiect

Documentul ține evidența inventarului, deciziilor, progresului și blocajelor, ca lucrul să poată continua între sesiuni.
Ultima actualizare: 2026-11-06.

## 1. Inventarul materialelor colectate

Sursa: copia site-ului vechi (WordPress + Elementor) făcută pe 2026-11-06, înainte de mutarea hostingului.
Site-ul vechi nu mai răspunde la adresa veche (după mutare are certificat self-signed), deci copia din repo este singura sursă.

| Material | Locație în repo | Stare |
|---|---|---|
| Texte pagini (4) | `texte/*.md`, `texte/pagini-complete/*.txt` | complete |
| HTML pagini | `texte/*.html`, `mirror/` | complete |
| Biblioteca Media (42 fișiere) | `imagini-originale/` | 10 originale trunchiate la descărcare; 7 au variante intacte în `mirror/` (2048 px sau 1024 px) |
| Logo | `mirror/elgarom.ro/wp-content/uploads/2023/08/logo-white.png` (1920×647, PNG transparent) | complet |
| Export API WordPress | `wp-api/` | complet |
| Documente (PDF, certificate) | — | **niciunul pe site-ul vechi** |

### Fotografii
Fotografii reale Elgarom (folosite în site-ul nou):

| Fișier | Conținut | Folosire |
|---|---|---|
| `b0515c42…-scaled.jpg` (2560 px) | Linia de zincare – băi, pasarelă | hero secundar, Proces |
| `bc916fff…` (2048 px, din mirror) | Linia – pod transportor, protecții | Capacități |
| `ac3deb71…` (1024 px, din mirror) | Linia – vedere de sus, dispozitive | Servicii |
| `bdc9a463…-1` (1366×528) | Panoramă băi | bandă panoramică |
| `2f6f71c6…-scaled.jpg` | Stația de epurare (rezervor „waste acid/zinc”) | Mediu |
| `3e8fe1fc…-scaled.jpg` | Echipamente de centrifugare cu palan | Top coating piese mici |
| `53f83fec…` (2048 px) | Laboratorul chimic | Calitate |
| `e69cf76d…-scaled.jpg` | Laborator – analiză | Calitate |
| `cd39d07f…jpg` (10016 px) | Hală/recepție cu logo Elgarom | Despre |
| `Elgarom-scaled.jpg` | Recepție cu logo (2026) | Despre |
| `37aec455…`, `9a892617…`, `f5a589b3…` | Sală de ședințe, coridor, spațiu personal | galerie (opțional) |
| `7a1b32c5…` | trunchiat, fără variantă intactă | **pierdut** |

Imagini demo ale temei WordPress (peisaje, animale, deșert, „Team-*.jpg” – portrete de stoc): **nu se folosesc**. Portretele nu reprezintă angajați Elgarom.
`pic-1.jpg` și `19fbcffd….png` sunt trunchiate și nu au variantă intactă.

## 2. Informații confirmate (din site-ul vechi)

- Serviciu: **zincare electrolitică slab acidă** (acoperire electrolitică cu zinc) – nu zincare termică.
- Execuție **pe dispozitive** (agățat) și **în tamburi**.
- Piese pe dispozitive: dimensiuni de până la **1900 × 1200 × 450 mm**.
- Tamburi: capacitate maximă de încărcare **100 kg** piese.
- Linie complet automatizată, proiectată și construită împreună cu parteneri europeni cu peste 40 de ani de experiență (experiența aparține partenerilor, nu Elgarom).
- Monitorizare electronică a parametrilor tehnologici; depunere controlată a stratului.
- Chimie: agenți de luciu, soluții de pasivare și top coating.
- Linie de centrifuge pentru top coating (sigilare) la piese mici: imersare, scurgere, centrifugare, uscare (polimerizare).
- Stație automată de epurare și neutralizare a apelor reziduale acido-alcaline.
- Laborator chimic: analize ale băilor active, analize chimice și spectrofotometrice ale apei tratate, determinarea grosimii stratului de zinc.
- Materiale menționate: oțel carbon cu conținut scăzut / mediu / ridicat de carbon, oțeluri inoxidabile, oțeluri aliate și înalt aliate (de confirmat tehnic – vezi §3).
- Piese de serie individuale, mici și mari; industrii diverse (nespecificate).
- Contact: Vânzări 0752 144 291, Tehnic 0748 118 339, office@elgarom.ro, vanzari@elgarom.ro, Str. Barajului nr. 3, Târgu Mureș, jud. Mureș.
- Grup: Argento Group (argento-group.com); companii surori în link-uri: Fepo (fepo.ro), Euronarcis (euronarcis.com).
- Identitate: logo cu trei profile „L” imbricate; teal `#14AD95`, bleumarin `#28387B`.

## 3. Informații lipsă – de completat în administrare

Marcate în platformă ca „De completat” și **nepublicate** până la confirmare:

- Certificări concrete (site-ul vechi menționează „sisteme certificate de asigurare a calității”, fără standard sau număr de certificat).
- Grosimi de strat oferite, tipuri de pasivare (culoare, trivalentă etc.), ore de test în ceață salină.
- Dimensiunile băilor, numărul de băi, capacitate lunară, program de lucru.
- Dimensiuni/greutate maximă per piesă în tambur (se cunoaște doar încărcarea de 100 kg).
- Greutatea maximă pe dispozitiv.
- Industrii și clienți deserviți, proiecte de referință (cu acordul clienților).
- Descrierea Argento Group (an, companii, rol Elgarom în grup), logo Argento.
- Date firmă: denumire juridică, CUI, nr. Reg. Com. – necesare pentru politici și date structurate.
- Confirmarea tehnică pentru zincarea oțelurilor inoxidabile și înalt aliate (lista provine din formularul vechi).
- Durabilitate: nicio valoare în ani nu are sursă. Secțiunea de durabilitate explică factorii, fără ani; administratorul poate adăuga valori doar cu sursă și condiții.

## 4. Tabel de migrare

| URL vechi | Pagină nouă | Conținut reutilizat | Redirect |
|---|---|---|---|
| `/` | `/` Acasă | poziționare, beneficii, materiale | – |
| `/?page_id=824`, `/acasa` | `/` | – | 301 → `/` |
| `/despre-noi` | `/despre` | text despre linie, valori, calitate | 301 |
| `/?p=11`, `/?page_id=11` | `/despre` | – | 301 |
| `/tehnologie` | `/procesul-de-zincare` + `/capacitati-tehnice` | linie, epurare, laborator, centrifuge, dimensiuni | 301 → `/procesul-de-zincare` |
| `/?p=19`, `/?page_id=19` | `/procesul-de-zincare` | – | 301 |
| `/contact` | `/contact` | date contact | – (același URL) |
| `/?p=24`, `/?page_id=24` | `/contact` | – | 301 |
| `/wp-sitemap.xml`, `/wp-sitemap-*.xml` | `/sitemap.xml` | – | 301 |
| `/feed/`, `/comments/feed/` | `/` | – | 301 |
| `/wp-content/uploads/…` imagini reale | `/media/…` | fotografii | 301 per fișier (tabel redirect în administrare) |
| `/wp-admin`, `/wp-login.php`, `/xmlrpc.php` | – | – | 410 (fără redirect spre admin) |

Toate redirect-urile sunt înregistrări în baza de date (Administrare → SEO → Redirect-uri), editabile.

## 5. Decizii de arhitectură

| Decizie | Motiv |
|---|---|
| Laravel 13, PHP ≥ 8.3, MySQL/MariaDB | framework matur, suportat; Laravel 12 nu mai primește corecturi de bug din 08.2026 |
| HTML randat pe server (Blade) | SEO, funcționare fără JS |
| Vite: CSS/JS compilate înainte de livrare; `vendor/` inclus în pachet | hostingul nu are nevoie de Node/Composer |
| Three.js + GSAP încărcate dinamic doar pe Acasă | performanță |
| Fără workeri permanenți: emailuri în tabel „outbox”, trimise imediat după răspuns + reîncercare prin cron (`schedule:run`) sau, fără cron, la vizite admin | compatibil cPanel |
| Fișiere private în `storage/app/private`, servite prin controller cu autorizare | upload-urile nu sunt executabile și nu sunt publice |
| Actualizări: ZIP cu `manifest.json` semnat Ed25519 (libsodium), cheie publică în cod | autenticitate fără cheie privată pe server |
| Asistent AI: API Anthropic Claude, apelat doar de server | chei pe server, limite și buget |
| Conținut în română; tabele cu coloană `locale` și rute pregătite pentru prefix de limbă | alte limbi fără traduceri incomplete publicate |

## 6. Progres

- [x] Backup site vechi, inventar, tabel de migrare
- [ ] Direcție vizuală + homepage cu scena 3D
- [ ] Pagini și migrarea conținutului
- [ ] Backend, administrare
- [ ] Formular ofertă, AI, Google
- [ ] Securitate, actualizări, verificări, pachet cPanel

## 7. Blocaje / întrebări pentru beneficiar

Nu blochează construcția; se completează în administrare:
1. Datele din §3 (certificări, date juridice firmă, Argento Group).
2. Cheia API Anthropic (pentru asistentul AI) – se introduce în Administrare → Asistent AI.
3. Date SMTP (server, utilizator, parolă) – Administrare → SMTP.
4. Client OAuth Google (pentru Search Console / GA4 / Business Profile) – Administrare → Google.
