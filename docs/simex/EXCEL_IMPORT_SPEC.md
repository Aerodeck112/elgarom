# Importul modelelor Excel

## Unde

Calcul de costuri → Modele Excel (permisiunea `formulas.manage`, cu reconfirmarea parolei și a codului 2FA),
sau din linia de comandă: `php artisan simex:modele /cale/catre/director`.

## Ce se întâmplă la încărcare

1. Se acceptă numai `.xlsx`, până la 20 MB. Un fișier identic (același SHA-256) nu se încarcă a doua oară.
2. Se verifică identitatea: foile așteptate există și câteva celule-etichetă conțin textul așteptat (`requires` din definiție).
3. Se caută funcții nesuportate și legături către alte fișiere; oricare dintre ele respinge modelul.
4. Se recalculează **toate** formulele și se compară cu valorile salvate de Excel (toleranță relativă 1e-9 pentru numere, egalitate pentru text).
5. Rezultat: `validat` (zero diferențe) sau `respins` (cu lista primelor celule diferite). Raportul se păstrează.
6. Un model `validat` devine `activ` numai prin acțiunea explicită a unei persoane. Modelul activ anterior devine `retras`.

Macro-urile nu sunt citite și nu sunt executate; prezența lor este doar semnalată.

## Definiția unui calculator (`simex/app/Calc/Definitions/*.php`)

Conține doar adrese și etichete, niciodată valori: fișierul așteptat, rolul fiecărei foi
(`input`, `params`, `reference`, `doc`), celulele de stare și de rezultat, opțional rândurile de ofertă.
Celulele de intrare nu sunt enumerate: sunt cele pe care workbook-ul însuși le marchează ca deblocate
(sau, la CUT E 600, cu umplerea galbenă `FFF2CC`).

## O versiune nouă a unui fișier

Dacă adresele rezultatelor nu s-au schimbat, fișierul nou se încarcă direct. Dacă s-au mutat celule,
validarea de identitate îl respinge și definiția trebuie actualizată printr-o actualizare a aplicației.
Calculele vechi rămân legate de versiunea cu care au fost făcute.

## Limitări cunoscute

- Tabele de date What-If, tabele pivot, `INDIRECT`, `OFFSET`, `LET`, `XLOOKUP`, `FILTER` nu sunt suportate (nu apar în fișierele actuale).
- Formule matriceale pe mai multe celule: suportate, dar neexersate de fișierele actuale.
- Formatele de dată și de număr complexe sunt afișate simplificat; valorile nu sunt afectate.
