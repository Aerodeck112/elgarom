Versiunea 0.1.6

- Interfața după aplicația ARGENTO: antet cu firma emitentă, numărul următoarei oferte, «+ Ofertă nouă», cursul EUR, data, utilizatorul, Administrare și Ieșire; tab-uri Dashboard, Ofertă piesă, Tarife, Ofertă ștanță, Tutorial, Oferte, Integrare AI (restul modulelor la «Mai multe»).
- Calculatorul pe secțiuni numerotate (acordeon), cu banda «Rezultat final» mereu vizibilă (cost, adaos, preț fără TVA, preț final cu echivalent EUR), avertizări pe fiecare secțiune, bara «N avertizări active — Vezi registrul» și Registrul de avertizări.
- «Drafturi salvate», «Resetează la exemplul inițial» și secțiunea finală «Audit final & generare ofertă» (salvare draft, creare ofertă).
- Dashboard: Noutăți (cu «Am văzut»), Acțiuni rapide, De verificat, fluxul ofertelor, valoarea ofertelor pe luni, structura costului ultimei ștanțe, ultimele oferte.
- Registru oferte cu indicatori și acțiuni Vezi / Editează / Șterge; ștergerea este permisă doar administratorului și rămâne în jurnalul de audit.
- Pagini noi: Tarife (foile de parametri ale modelelor și tarifele orare), Tutorial, Integrare AI (email de însoțire, rezumat de cerere, traducere; rulate pe server, fără a modifica cifre).
- Cursul EUR se preia de la BNR: automat prin cron (comanda simex:curs) sau manual din insigna EUR din antet.
