# GRUP SIMEX Industrial CRM – arhitectură

Stare la 12.11.2026, versiunea 0.1.0. Codul este în `simex/`; site-ul ELGAROM din `platforma/` rămâne o aplicație separată.

## Decizii

| Decizie | Motiv |
|---|---|
| Aplicație Laravel 13 separată (`simex/`), PHP ≥ 8.3, MySQL/MariaDB | alt domeniu, altă bază de date și alt ciclu de livrare decât site-ul Elgarom |
| Infrastructura preluată din `platforma/`: 2FA, audit, backup, actualizare ZIP semnată, coadă de email, instalator | cod deja verificat în producție |
| HTML randat pe server (Blade) + module JavaScript mici, fără framework SPA | funcționează pe cPanel fără Node; CSP strict (fără `unsafe-eval`, fără scripturi inline) |
| Tailwind CSS 4 + componente proprii, compilate cu Vite înainte de livrare | serverul nu are nevoie de Node sau Composer; `vendor/` și `public/build/` sunt în pachet |
| Motor propriu de formule Excel în PHP | PhpSpreadsheet a fost măsurat pe fișierele reale: 123 s pe un workbook, o funcție neimplementată, 363 de rezultate greșite. Motorul propriu: sub 1 s pe fișier, 0 diferențe |
| Workbook-ul Excel este „versiunea formulei” | nicio formulă nu este rescrisă de mână, deci nu poate fi rescrisă greșit; foaia `Interfata_API` din fișierul de frezare cere exact acest mod de lucru |
| Workbook-urile nu sunt în depozit și nici în pachet | conțin tarife și adaosuri; depozitul GitHub este public. Se încarcă din panou |
| Fără workeri permanenți; `schedule:run` din cron | compatibil cPanel |

## Structură

```
simex/app/Calc/Engine      cititor xlsx, parser, evaluator, funcții Excel
simex/app/Calc             definiții de calculatoare, sesiune de calcul, validator, serviciu
simex/app/Services/Quotes  aritmetica ofertei, snapshot, PDF, Excel
simex/app/Admin            formulare declarative, resurse de bibliotecă, grupuri de setări
simex/app/Http/Controllers controllere subțiri; validare și autorizare pe server
simex/config/permissions.php   roluri și permisiuni
simex/config/simex.php         versiune, chei publice de actualizare, limite
```

Rute: site public la `/`, platforma internă la `/crm` (autentificare + 2FA obligatoriu pentru toate rolurile).

## Fluxul unui calcul

1. Browserul trimite `{foaie, intrări}` la `POST /crm/api/calculator/{cheie}`.
2. `CalcService` deschide modelul activ (din cache, după hash), aplică setul de parametri activ, apoi intrările.
   Sunt acceptate doar celulele marcate ca intrări în workbook, pe foile permise rolului.
3. `Workbook` evaluează leneș celulele cerute și întoarce foaia și rezultatele. Browserul nu calculează nimic.
4. La salvare se îngheață: intrările, rezultatele, hash-ul modelului, setul de parametri, tariful utilajului, versiunea motorului.

## Ce este verificat

- 38 de teste automate (38.105 aserțiuni), rulate pe SQLite și pe MariaDB 10.11.
- Instalarea din pachet cu `install.php` pe MariaDB, apoi `/health`, `/`, `/crm`, `/.env` (404).
- Verificare în browser real (Chromium): autentificare cu 2FA, calculator OKAMOTO și Laser, ofertă, PDF, site public.

## Ce nu este verificat

- Rularea pe serverul cPanel real al beneficiarului (versiune PHP, limite, mod_rewrite).
- Trimiterea reală prin SMTP (nu există un server de test aici).
- Traducerile EN/DE ale etichetelor din PDF de către un vorbitor nativ.
