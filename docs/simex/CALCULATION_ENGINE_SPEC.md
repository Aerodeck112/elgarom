# Motorul de calcul – specificație

## Principiu

Calculele sunt deterministe și rulează numai pe server. AI nu participă la niciun calcul.
Formulele nu sunt rescrise: motorul evaluează direct formulele din workbook-ul Excel validat.

## Componente (`simex/app/Calc`)

| Clasă | Rol |
|---|---|
| `Engine\XlsxModelReader` | citește `.xlsx`: constante, formule (inclusiv partajate și matriceale), rezultatele salvate de Excel, nume definite, validări de date (și cele din extensia Excel 2010), stiluri, celule deblocate. Nu citește și nu execută macro-uri |
| `Engine\FormulaParser` | text → arbore sintactic; precedența Excel (`-2^2 = 4`, `^` asociativ la stânga) |
| `Engine\Workbook` | evaluare leneșă cu memorare, detectarea referințelor circulare, intersecție implicită, mod matriceal |
| `Engine\Functions` | 58 de funcții folosite de fișiere, plus câteva înrudite |
| `ModelValidator` | recalculează toate formulele și le compară cu valorile din fișier |
| `CalcSession` | aplică intrări doar în celulele permise; construiește foile și rezumatul |
| `CalcService` | model activ, parametri activi, salvare cu snapshot |

## Semantică Excel respectată

- Numere în dublă precizie; comparațiile de numere la 15 cifre semnificative (`0,1+0,2 = 0,3` este adevărat).
- Celula goală este 0, `""` sau FALS după context; un rezultat `""` este text, nu celulă goală.
- Ordinea tipurilor: numere < text < valori logice; textul se compară fără diferență de majuscule.
- Erorile (`#N/A`, `#VALUE!`, `#DIV/0!`, `#REF!`, `#NAME?`, `#NUM!`) se propagă; `IFERROR`/`ISNA` le prind.
- `SUM`, `MAX` etc. ignoră textul din zone, dar convertesc argumentele directe.
- Criteriile `COUNTIF`/`SUMIFS`: operatori, metacaractere, `"<>"` pentru celule nevide.
- Număr → text la concatenare: formatul General, 15 cifre semnificative, separator zecimal punct (ca în fișiere).
- `TODAY()` este fixată la data calculului, ca rezultatul să poată fi reprodus.

O funcție necunoscută oprește calculul cu eroare explicită; motorul nu ghicește.

## Rotunjiri

- În modele: exact cele din formule (`ROUND`, `ROUNDUP`), jumătatea se rotunjește în sus în valoare absolută.
- În ofertă (`QuoteService`, versiunea `oferta-1`): total linie = rotunjire la 2 zecimale din cantitate × preț × (1 − discount);
  subtotal = suma liniilor neopționale; net = rotunjire(subtotal × (1 − discount general)); TVA = rotunjire(net × cotă); total = net + TVA.
  Marja = (net − cost) / net și este necunoscută dacă o linie nu are cost.

## Unități

Unitățile sunt cele din workbook (mm, min, ore, kg, EUR/RON) și apar în etichetele rezultatelor.
Modelele de ore dau ore; costul = ore × tariful orar al utilajului, numai dacă tariful a fost introdus.

## Versiuni și snapshot

Un calcul salvat păstrează: hash-ul SHA-256 al workbook-ului, numărul setului de parametri și valorile lui,
tariful utilajului, versiunea motorului (`config/simex.php → engine_version`), data calculului și rezultatele.
Schimbarea ulterioară a tarifelor sau a modelului nu modifică un calcul salvat și nici o ofertă emisă.

## Permisiuni

| Acțiune | Permisiune |
|---|---|
| folosirea calculatoarelor | `calc.use` |
| vederea foilor de parametri și tarife | `rates.view` |
| modificarea parametrilor (set nou, versionat) | `rates.edit` |
| vederea costurilor interne | `costs.view` |
| încărcarea și activarea unui model | `formulas.manage` + parolă și cod 2FA recente |

## Performanță măsurată

Citire 0,01–0,14 s pe fișier; recalcularea completă a celui mai mare (14.753 formule) 0,6–0,9 s;
o cerere obișnuită din interfață 5–220 ms. Modelul citit este păstrat în cache după hash.
