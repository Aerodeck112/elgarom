# Ghid de administrare

Adresa: `https://domeniul-dvs.ro/admin`. Autentificarea cere parolă și cod 2FA, la fiecare sesiune. După 30 de minute de inactivitate, sesiunea se închide (durata se poate schimba din Securitate).

## Roluri

| Rol | Poate |
|---|---|
| Administrator | tot |
| Editor conținut | pagini, servicii, capacități, aplicații, proiecte, documente, întrebări, meniuri, media, SEO, redirect-uri, baza de cunoștințe AI; vede cererile |
| Vânzări / ofertare | cereri: stare, responsabil, note, fișiere, export CSV |
| Doar vizualizare | panou principal și lista cererilor, fără fișiere |

Permisiunile sunt verificate pe server la fiecare operațiune, nu doar ascunse din meniu.

Operațiunile sensibile cer din nou parola și un cod 2FA **nou** (nefolosit anterior):
- utilizatori;
- export de cereri;
- descărcarea și restaurarea backup-urilor;
- conectarea Google;
- instalarea actualizărilor.

## Conținut

- **Pagini și secțiuni:** fiecare pagină are date SEO și o listă de secțiuni ordonabile (Sus/Jos), care pot fi ascunse sau șterse. Modificările se salvează ca **ciornă**. „Previzualizează ciorna” deschide un link semnat, valabil 30 de minute. Pe site apar doar după **Publică**. La schimbarea adresei unei pagini se creează automat un redirect 301.
- **Tipuri de secțiuni:** text, text cu imagine, beneficii, etape numerotate, fotografie pe toată lățimea, galerie, date tehnice confirmate (automat), desen de gabarit (automat), durabilitate, îndemn la acțiune, servicii, industrii, proiecte, documente, întrebări (automat), Argento Group, formular scurt, contact. Pe Acasă există în plus „Scena 3D – textele etapelor”.
- **Formatare:** câmpurile „Conținut” acceptă Markdown (**îngroșat**, liste, titluri `##`, linkuri). HTML-ul este eliminat, ca să nu se poată introduce scripturi.
- **Capacități tehnice:** pe site apar **doar** valorile marcate „Confirmat”, iar pentru ele sursa este obligatorie. Valorile necunoscute rămân în lista „De completat” din panoul principal.
- **Proiecte:** numele clientului apare doar dacă este bifat „Clientul a fost de acord”.
- **Documente:** fișierele (PDF/JPG/PNG) sunt private. Doar cele marcate „Public” se pot descărca de pe site.
- **Meniuri, companie, subsol:** din „Meniuri” și „Companie, contact, subsol”. Telefoanele, emailurile și adresa apar peste tot în site și în datele structurate.

## Bibliotecă media

La încărcare (JPG/PNG/WebP, max. 20 MB), fiecare imagine este:
- reencodată, fără metadate EXIF;
- redusă la cel mult 2560 px;
- convertită în variante WebP de 480, 960, 1600 și 2400 px, pentru încărcare rapidă.

Pentru fiecare imagine completați textul alternativ (accesibilitate și SEO). Puteți alege și punctul de interes pentru decupare. Ștergerea este blocată dacă imaginea este folosită.

## Cereri de ofertă

Fiecare cerere primește un număr unic (`ELG-2026-00001`). Pagina cererii conține:
- datele completate de client și fișierele (descărcabile doar de utilizatorii cu permisiune; fiecare descărcare apare în jurnal);
- stare, responsabil, note interne și istoric;
- starea emailurilor trimise.

Exportul CSV respectă filtrele curente și e protejat împotriva formulelor injectate în Excel. Administratorul poate șterge definitiv o cerere, de exemplu la o solicitare GDPR.

Cererea se salvează în baza de date **înainte** de trimiterea emailurilor. Dacă SMTP nu funcționează, clientul vede totuși confirmarea, iar emailurile rămân în coadă și se reîncearcă automat: după 1, 2, 4, 8… minute, până la 6 ore, de 8 ori. Coada se vede în SMTP și notificări, cu buton „Reîncearcă”.

## SMTP și notificări

Datele se iau din cPanel → Email Accounts → Connect Devices:
- server: de obicei `mail.domeniu.ro`;
- port: 465 SSL sau 587 STARTTLS;
- utilizator: adresa completă;
- parola contului.

Parola se stochează criptat. Trimiteți un email de test după salvare. Destinatarii notificărilor se scriu câte unul pe rând.

## Asistent AI

1. Cheia API se creează în console.anthropic.com → API Keys și se introduce în Asistent AI. Se stochează criptat și nu ajunge niciodată în browser.
2. Modelul implicit este Claude Opus 5.5. Se poate alege Claude Sonnet 5.5 sau Claude Haiku 5.5, la cost mai mic. Efortul implicit este „scăzut”, potrivit pentru chat.
3. Setați limitele:
   - mesaje pe conversație (20);
   - mesaje pe IP pe oră (30);
   - buget zilnic (2 USD) și lunar (30 USD);
   - păstrarea conversațiilor (30 de zile).
4. „Testează conexiunea” trimite un mesaj scurt, inclus în buget.
5. **Baza de cunoștințe** conține informațiile aprobate. Asistentul folosește doar intrările active, serviciile publicate, întrebările frecvente și capacitățile confirmate. Fiecare salvare marchează intrarea ca verificată de dumneavoastră, cu data.
6. Asistentul:
   - spune că este AI;
   - nu inventează prețuri, termene, garanții sau capacități;
   - nu confirmă acceptarea tehnică a unei piese;
   - nu are acces la administrare, la alte cereri sau la fișiere.

   Poate propune precompletarea formularului, iar vizitatorul decide dacă o folosește.
7. Dacă asistentul nu e configurat, bugetul e depășit sau API-ul nu răspunde, butonul dispare sau chat-ul trimite vizitatorul la formular și la telefon.
8. În statistici vedeți costul pe zi, tokenii, refuzurile din cauza limitelor, erorile și conversațiile care au dus la o cerere.

## Google și marketing

Pagina are patru părți:

1. **Identificatori:** GTM, GA4, Google Ads (conversie), codul de verificare Search Console, linkul Business Profile. Se acceptă doar identificatori validați după format, niciodată cod. Un singur mod este activ, Direct sau GTM, ca evenimentele să nu fie numărate de două ori.
2. **OAuth:** client OAuth din Google Cloud, URI de redirecționare afișat în pagină, permisiuni alese explicit. Stările posibile sunt Neconfigurat, Neconectat, Conectat, Autorizare expirată și Acces necesar.
3. **Date citite prin API:**
   - Search Console: clicuri, afișări, interogări, sitemap-uri și trimiterea sitemap-ului;
   - GA4: sesiuni, utilizatori, evenimente cheie;
   - Business Profile: locații, comparate cu datele din site;
   - Google Ads: doar raport de campanii. Bugetele și campaniile nu se modifică niciodată din platformă.
4. **Pași în conturile Google:** verificarea DNS, activarea API-urilor, developer token-ul Ads, aprobarea Business Profile.

**Cookies:** modul implicit este „De bază”: nicio etichetă Google nu se încarcă înainte de acord. Modul „Avansat” folosește Consent Mode v2 cu valori „denied”. Măriți „Versiunea politicii” pentru a cere din nou acordul vizitatorilor.

## Securitate

Pagina Securitate cuprinde:
- verificări automate: HTTPS, depanare, `.env` inaccesibil, instalator, 2FA;
- sesiunile active, care pot fi închise;
- încercările eșuate.

Jurnalul de audit înregistrează autentificările, modificările, exporturile, descărcările de fișiere, backup-urile și actualizările. Parolele și cheile nu se înregistrează niciodată.

**Acces pierdut:**
- Un utilizator își poate folosi codurile de recuperare.
- Un administrator poate reseta 2FA altui utilizator.
- Dacă singurul administrator a pierdut accesul, rulați în cPanel → Terminal `php artisan elgarom:admin email@firma.ro`. Comanda generează o parolă temporară și resetează 2FA.

## Backup și restaurare

Backup → „Creează backup acum”:
- conține baza de date, imaginile, documentele și fișierele cererilor;
- după creare, arhiva este redeschisă și fiecare hash este verificat.

Restaurarea creează întâi un backup de siguranță și cere scrierea cuvântului RESTAUREAZA. Backup-urile se pot descărca (cu reconfirmare) și se pot urca pe alt server.
