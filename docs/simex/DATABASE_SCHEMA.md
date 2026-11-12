# Schema bazei de date

Migrațiile sunt în `simex/database/migrations`. 37 de tabele. Sumele sunt `DECIMAL`, nu `FLOAT`.

| Grup | Tabele | Rol |
|---|---|---|
| Sistem | `users`, `sessions`, `cache`, `settings`, `mail_outbox`, `audit_logs`, `backups`, `system_updates`, `sequences` | conturi cu 2FA, setări (secretele criptate), coadă de email, jurnal, numerotare fără goluri |
| Grup | `entities` | companiile grupului: prezentare publică, siglă, date juridice, `legal_confirmed_at` |
| CRM | `companies`, `contacts`, `activities`, `documents`, `suppressions` | clienți, contacte, istoric și sarcini, documente private, listă globală de excludere (doar hash-uri) |
| Cereri | `rfqs`, `rfq_items` | cereri de ofertă și reperele lor |
| Bibliotecă | `materials`, `machines`, `operations`, `suppliers`, `products`, `price_history` | prețuri și tarife cu istoric; `NULL` înseamnă „neconfigurat”, niciodată zero |
| Calcul | `calc_model_versions`, `calc_parameter_sets`, `calculations` | workbook-uri încărcate și validate, seturi de parametri versionate, calcule salvate cu snapshot |
| Scule | `tool_projects`, `tool_cost_lines`, `product_tool_project` | ștanțe și matrițe, linii de cost pe faze, legătura sculă–piese |
| Zincare | `zinc_jobs` | fișe tehnice; fără cost până la validarea unei formule |
| Oferte | `quotes`, `quote_items`, `quote_events`, `quote_templates` | oferte cu revizii, `snapshot` JSON la emitere, istoric |

## Reguli

- `quotes(number, revision)` este unic; o revizie nouă păstrează numărul și crește `revision`; `is_current` marchează ultima.
- `quotes.snapshot` conține emitentul, clientul, pozițiile, condițiile, cursul și, într-o secțiune internă care nu ajunge în PDF,
  costurile și referințele calculelor. Se scrie o singură dată, la aprobare.
- `calculations.results` conține rezultatele, parametrii, tariful utilajului și versiunea motorului din momentul salvării.
- `calculations.locked_at` se setează când calculul intră într-o ofertă emisă.
- Ștergerile de companii, contacte, cereri, oferte și calcule sunt logice (`deleted_at`).

## Planificat (fără tabele încă)

Liste și campanii de email, dosare de prospectare, portal de cereri pentru clienți – vezi `MODULES_ROADMAP.md`.
