# Auditul fișierelor Excel de calcul

Fișiere primite la 12.11.2026, salvate cu Microsoft Excel 16 la 06.11.2026. Niciunul nu conține macro-uri sau legături externe.

> Depozitul GitHub este public. De aceea aici nu sunt reproduse tarife, adaosuri, regii sau prețuri din fișiere,
> iar fișierele nu sunt în depozit. Documentul descrie structura, logica și rezultatul verificării.

## Inventar

| Fișier | Foi | Formule | Ce calculează | Calculator |
|---|---|---|---|---|
| `Calculator_OKAMOTO_ore_manopera.xlsx` | 7 | 314 | ore de manoperă la rectificare plană | `rectificare-okamoto` |
| `Calculator_Ore_Frezare_GENOS_M460_VE_e_v5_3.xlsx` | 10 | 1.108 | ore de frezare pentru plăci de ștanță | `frezare-genos-m460` |
| `Calculator_Ore_Strunjire_GENOS_L3000e_MY_v1_3_1.xlsx` | 11 | 2.008 | ore de strunjire, unicat și lot | `strunjire-genos-l3000` |
| `Calculator_CUT_E_600_v18.xlsx` | 11 | 6.577 | timp de electroeroziune cu fir | `electroeroziune-cut-e600` |
| `Calculator_Cost_Laser_Abkant_Finisaje_v321_MODEL.xlsx` | 25 | 14.753 | cost și preț pentru piese din tablă | `laser-abkant-finisaje` |
| **Total** | **64** | **24.760** | | |

Patru fișiere dau **ore**, nu bani. Numai fișierul de tablă conține tarife și calculează cost și preț.
Nu există un fișier pentru zincare și nici unul pentru prețul ștanțelor.

## Verificare

| Verificare | Rezultat |
|---|---|
| Fiecare formulă recalculată și comparată cu valoarea salvată de Excel | 24.760 din 24.760 identice |
| 11 scenarii cu intrări completate, recalculate independent cu LibreOffice 24 și comparate celulă cu celulă | 37.880 din 37.880 identice |
| Funcții Excel folosite / suportate | 58 / 58 |
| Formule matriceale | 656, toate pe o singură celulă, toate verificate |

Scenariile acoperă: rectificare (2), frezare (3: rapid, CAM, material călit), strunjire (3: lot, piesă prea lungă blocată corect, CAM),
electroeroziune (2: rapid și detaliat), tablă (1: două repere cu laser, abkant, sudură, zincare, ambalare, ofertă emisibilă).
Testele sunt în `simex/tests/Feature/WorkbookModelsTest.php` și rulează când fișierele sunt puse în
`storage/app/private/calc-models/source`.

## Cum sunt construite fișierele

- **Intrări**: celule deblocate, cu liste derulante și limite de validare. La CUT E 600 intrările sunt marcate doar prin culoare.
- **Parametri**: foi separate (`Parametri`, `Regimuri`, `03_TARIFE_UT`, `04_MATERIALE` …). Platforma le tratează ca set global, versionat.
- **Stare**: fiecare fișier are o celulă de stare (OK / ATENȚIE / BLOCAJ sau PASS / VERIFICĂ). Platforma nu afișează ore sau total cât timp starea este de blocaj.
- **Calibrare**: foile `Calibrare_85`, `Validare`, `Istoric_viteze` compară estimările cu timpii reali. Acum sunt goale, deci fișierele își declară singure orele „calibrare neconfirmată”.

### Logica rezultatelor principale

| Calculator | Rezultat | Logică (din manualul fișierului) |
|---|---|---|
| Rectificare | `Calculator!B46` ore | (degroșare + finisare + spark-out + diamantare) × factori de complexitate și calibrare + timpi auxiliari + rezervă tehnologică + programare, împărțit la 60 |
| Frezare | `Interfata_API!F42` ore ocupare lot | ciclu lot = ciclu prima piesă + (n−1) × ciclu fără probă; ocupare = ciclu + n × (încărcare + măsurători) + reglaj + scule noi |
| Strunjire | `Calculator!H31` ore ocupare lot | ocupare lot = ocupare prima piesă + (n−1) × ocupare piesă de serie |
| Electroeroziune | `Estimare_rapida!B23` sau `Estimare_detaliata!B50` ore | (contur + intrări) × grosime ÷ viteza din matrice × factor de complexitate, plus finisări, înfilări și prinderi; factorul de corecție se aplică doar tăierii |
| Tablă | `13_OFERTA!I44` total, rândurile 11–40 pe reper | material = cost + adaos de material; procese = (cost + regie) × (1 + adaos); finisaje externe = cost + marjă; total afișat doar când `14_VERIFICARI!B4` = PASS |

Fișierul de frezare are foaia `Interfata_API`, cu chei stabile pentru intrări și ieșiri, scrisă anume pentru o platformă de ofertare; rezultatele se citesc din ea.

## Observații pentru beneficiar

1. **Strunjire**: fișierul conține un reper de exemplu completat (B-1024). Un calcul nou pornește cu aceste valori; ele trebuie înlocuite.
2. **Electroeroziune**: ambele estimări afișează «ATENȚIE – FACTOR IMPUS, nevalidat». Factorul de corecție este impus manual până când există lucrări de validare.
3. **Tablă, `13_OFERTA!B5`**: data ofertei goală se afișează în Excel ca 00:00; fără efect asupra calculului.
4. **Tablă**: foaia de finisaje tratează zincarea ca serviciu cumpărat, cu tarif de furnizor. Acesta nu este un model de cost intern pentru Elgarom.
5. **Calibrare**: cu zero lucrări reale introduse, intervalele de încredere sunt cele implicite. Pe măsură ce se introduc timpi reali în foile de calibrare (permisiunea `rates.edit`), estimările devin mai sigure.
6. Nu au fost găsite referințe rupte, formule contradictorii sau celule cu erori în starea livrată.

## Ce nu a putut fi validat

- Corectitudinea tehnologică a parametrilor (viteze, regimuri, tarife): motorul reproduce fișierele, nu le judecă.
- Compararea cu Excel pe scenarii completate: scenariile au fost recalculate cu LibreOffice, nu cu Excel. Valorile inițiale ale fișierelor sunt însă cele scrise de Excel și coincid integral.
