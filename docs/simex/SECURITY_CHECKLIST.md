# Securitate – listă de verificare

Stare: ✅ implementat și testat · ◐ implementat, netestat automat · ☐ de făcut.

| Cerință | Stare | Cum |
|---|---|---|
| Autentificare cu 2FA | ✅ | TOTP obligatoriu pentru toate rolurile, coduri de recuperare, protecție la reutilizarea codului |
| Limitarea încercărilor | ◐ | pe cont și pe IP la autentificare; `throttle` pe 2FA și pe API-ul de calcul |
| Sesiuni | ◐ | în baza de date, criptate, expirare la inactivitate, revocare din panou |
| Autorizare pe server | ✅ | middleware `admin.can` pe fiecare rută; test de izolare pe roluri |
| Operații sensibile | ◐ | utilizatori, backup, actualizare, activarea modelelor, confirmarea datelor juridice cer parola și un cod 2FA recent |
| CSRF | ✅ | toate formularele și cererile JSON |
| XSS | ✅ | Blade cu escapare; CSP cu nonce, fără scripturi inline și fără `unsafe-eval`; Markdown fără HTML |
| SQL injection | ✅ | Eloquent și interogări parametrizate; căutările escapează `%` și `_` |
| Antete | ✅ | CSP, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, HSTS pe HTTPS, `no-store` pe `/crm` |
| Separarea datelor publice | ✅ | site-ul public citește doar `entities` și câteva setări; datele juridice apar doar după confirmare; test dedicat |
| Costuri interne și marje | ✅ | permisiunile `costs.view` și `margins.view`; un utilizator fără drept nu le vede și nu le poate suprascrie; PDF-ul și snapshot-ul clientului nu le conțin |
| Tarife și parametri | ✅ | modificabile doar cu `rates.edit`; istoric și audit la fiecare schimbare |
| Formule | ✅ | utilizatorii scriu doar constante în celule de intrare; un text care începe cu `=` rămâne text; modelele se schimbă doar cu `formulas.manage` |
| Fișiere încărcate | ◐ | workbook-uri: doar `.xlsx`, validate; sigle: tip verificat, SVG fără scripturi; stocare privată, servite prin controller |
| Chei API și SMTP | ◐ | criptate în baza de date cu `APP_KEY`, niciodată afișate după salvare, niciodată trimise în browser |
| Actualizare ZIP | ✅ | semnătură Ed25519, hash pe fișier, blocarea traversării de directoare și a căilor protejate, backup înainte (teste în `PackageVerifierTest`) |
| Jurnal de audit | ✅ | autentificări, tarife, parametri, modele, oferte, utilizatori, setări |
| Backup și restaurare | ◐ | manual și zilnic prin cron, cu verificare; `recover.php` |
| Date trimise către AI | ✅ | nimic nu este trimis: integrarea nu este încă implementată; comutatorul pentru texte din oferte este oprit implicit |
| Workbook-uri confidențiale | ✅ | nu sunt în depozit și nici în pachet; se încarcă din panou în stocarea privată |
| GDPR – excludere | ◐ | «nu contacta» pe companie și contact, listă globală cu hash-uri, bază legală pe companie |
| GDPR – export și ștergere la cerere | ☐ | Faza 4; până atunci, din baza de date, de către administrator |
| Politici de retenție | ◐ | jurnalul de audit (implicit 365 de zile) și fișierele temporare; pentru datele CRM de stabilit cu beneficiarul |
| Scanare antivirus a fișierelor | ☐ | depinde de hosting (ClamAV); biblioteca de documente este în Faza 3 |

## De făcut de către beneficiar

1. Depozitul GitHub este **public**. Dacă nu este intenționat, faceți-l privat.
2. Păstrați cheia privată de semnare a actualizărilor în afara serverului.
3. Folosiți varianta A de amplasare (Document Root pe `public/`).
4. Stabiliți cine primește rolurile cu `rates.edit`, `formulas.manage`, `quotes.approve` și `updates`.
