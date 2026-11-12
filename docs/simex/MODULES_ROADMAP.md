# Module – stare reală

Stări: **funcțional** (implementat și testat), **parțial**, **în așteptarea datelor**, **planificat**.

| # | Modul | Stare | Observații |
|---|---|---|---|
| 1 | Dashboard | funcțional | KPI pe 30/90/365 zile, 4 grafice; valoare ofertată, acceptată și venit confirmat sunt indicatori separați |
| 2 | Clienți și contacte | funcțional | flux comercial, contacte, activități, sarcini, follow-up, câmpuri GDPR |
| 3 | Cereri de ofertă (RFQ) | funcțional | numerotare, repere, statusuri, legături către calcule și oferte |
| 4 | Calculator piese metalice | funcțional pentru tablă (laser, abkant, sudură, finisaje, ambalare); în așteptarea datelor pentru ștanțare, găurire, montaj, transport | fără Excel, operațiile lipsă afișează „Formulă în curs de configurare” |
| 5 | Calculator ștanțe și matrițe | parțial | orele de frezare, strunjire, electroeroziune cu fir și rectificare sunt validate; însumarea costurilor este o simplă adunare, formula de preț FEPO nu a fost furnizată |
| 6 | Calculator zincare | în așteptarea datelor | fișa tehnică și avertizările de gabarit funcționează; costul nu se calculează |
| 7 | Oferte comerciale | funcțional, fără trimitere | constructor, revizii, comparare, aprobare, snapshot, PDF RO/EN/DE, Excel |
| 8 | Produse și proiecte | funcțional | |
| 9 | Materiale și costuri | funcțional | cu istoric de preț |
| 10 | Utilaje și operații | funcțional | tarifele orare se introduc de către rolurile autorizate |
| 11 | Furnizori | funcțional | |
| 12 | AI Prospectare Europa | planificat (Faza 4) | cheia și modelul se pot configura; lipsește sursa de date B2B aprobată |
| 13 | Email Marketing | planificat (Faza 4) | există coada SMTP și lista de excludere |
| 14 | Șabloane și documente | planificat (Faza 3) | tabelele există; PDF-ul folosește deocamdată un șablon comun cu identitatea emitentului |
| 15 | Rapoarte | planificat (Faza 4) | indicatorii sunt în dashboard |
| 16 | Utilizatori și permisiuni | funcțional | 7 roluri, 33 de permisiuni, 2FA obligatoriu |
| 17 | Configurări | funcțional | generale, SMTP cu test, cheie API, website |
| 18 | Actualizare ZIP | funcțional (moștenit) | pachet semnat Ed25519, backup înainte, jurnal, `recover.php` |
| 19 | Audit și activitate | funcțional | |
| – | Website public | funcțional | siglele GRUP SIMEX și FEPO trebuie încărcate |

## Faza 3 – de făcut

Trimiterea ofertei prin email din platformă (cu PDF atașat), link securizat cu expirare pentru client, texte comerciale pe emitent și limbă,
bibliotecă de documente tehnice, propuneri de traducere cu AI care nu pot schimba cifrele.

## Faza 4 – de făcut

Prospectare cu sursă de date aprobată, email marketing, rapoarte cu export, portal de cereri, API pentru ERP,
cereri GDPR de export și ștergere din interfață, scenarii comparative de volum.

## Date care lipsesc de la beneficiar

1. Excel sau reguli pentru costul zincării (Elgarom).
2. Formula de preț pentru ștanțe și matrițe (FEPO): adaosuri, regie, risc.
3. Tarifele orare ale celor patru utilaje.
4. Datele juridice ale emitenților și siglele oficiale GRUP SIMEX și FEPO.
5. Cotă TVA, curs, praguri de marjă și de discount.
