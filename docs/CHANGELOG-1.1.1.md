# ELGAROM 1.1.1 – reparații la actualizare

- Reparat: actualizările înlocuiau fișierele `.htaccess`. În ele cPanel păstrează versiunea PHP a domeniului, așa că după actualizare site-ul putea trece pe PHP-ul implicit al serverului, iar actualizarea eșua și se restaura singură. Actualizările nu mai ating `.htaccess`, `.user.ini` și `php.ini`.
- Reparat: la actualizarea din 1.0.0, linkurile din meniurile și butoanele în germană, engleză și maghiară se salvau fără prefixul limbii (de ex. `/leistungen` în loc de `/de/leistungen`) și duceau la „pagina nu există”. Linkurile existente sunt corectate automat.
- Reparat: actualizarea 1.1.0 putea eșua (și se restaura singură) cu mesajul „Cannot access offset of type string on string”. În timpul actualizării, serverul (OPcache) mai folosea câteva secunde versiunea veche a unor fișiere. Codul care rulează în timpul actualizării nu mai depinde de asta, iar installer-ul invalidează explicit fiecare fișier PHP scris.
- Verificarea de după instalare nu mai anulează actualizarea pe serverele care blochează cererile către propriul domeniu (firewall, WAF, CDN). În acest caz verifică aplicația din interior. Un site care răspunde cu eroare după actualizare este în continuare restaurat automat.
- Comanda de cron este afișată cu PHP-ul de linie de comandă potrivit site-ului, la instalare și în Administrare → SMTP și notificări. Dacă site-ul sau cron-ul rulează pe un PHP mai vechi de 8.3, apare un mesaj clar cu versiunea folosită și cu pașii de rezolvare.
- Subsol: „POWERED BY VITIM”.
- Conținutul, cererile și setările existente nu se modifică, cu excepția linkurilor corectate.
