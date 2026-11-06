<?php

// Magyar tartalom (románból fordítva; közzététel előtt anyanyelvi lektorral ellenőriztetni).

$cta = ['cta', [
    'heading' => 'Horganyzandó alkatrésze van?',
    'text' => 'Küldje el, amit az alkatrészről tud – kezdetnek egy fénykép is elég. Műszaki csapatunk ajánlattal válaszol.',
    'button_label' => 'Ajánlatkérés', 'button_url' => 'page:solicita-oferta',
    'secondary_label' => 'Értékesítés hívása', 'secondary_url' => 'tel:+40752144291',
]];
$durability = ['durability', [
    'heading' => 'Meddig tart a védelem',
    'intro' => 'A cinkréteg kétféleképpen védi az acélt: gátat képez a fém és a környezet között, és az acél helyett maga korrodál. Hogy meddig tart, néhány konkrét tényezőtől függ.',
    'items' => [
        ['title' => 'Rétegvastagság', 'text' => 'A vastagabb réteg lassabban fogy el. A vastagságot az alkatrész követelményei szerint határozzuk meg, és laborban ellenőrizzük.'],
        ['title' => 'Passziválás', 'text' => 'A horganyzás után alkalmazott passziválás lassítja a cinkréteg korrózióját.'],
        ['title' => 'Top coat', 'text' => 'A kis alkatrészeknél elérhető kiegészítő lezárás növeli a korrózióállóságot.'],
        ['title' => 'Felhasználási környezet', 'text' => 'A nedvesség, a só, a vegyi anyagok és a páralecsapódás gyorsítják a korróziót. Ugyanaz az alkatrész beltérben és kültéren eltérően viselkedik.'],
    ],
    'note' => 'Az ábra minőségi szemléltetés, nem garancia. Az Ön alkalmazására vonatkozó védelmi időt a konkrét követelmények alapján egyeztetjük.',
]];
$group = fn ($heading) => ['group', [
    'heading' => $heading,
    'body' => 'Az ELGAROM az Argento Group tagja, olyan vállalatok mellett, mint a Fepo és az Euronarcis.',
    'companies' => [
        ['name' => 'Argento Group', 'url' => 'https://argento-group.com/', 'text' => ''],
        ['name' => 'Fepo', 'url' => 'https://fepo.ro/', 'text' => ''],
        ['name' => 'Euronarcis', 'url' => 'https://www.euronarcis.com/', 'text' => ''],
    ],
]];

return [
    'settings' => [
        'footer.text' => 'Az ELGAROM gyengén savas galvanikus horganyzást végez automatizált soron Marosvásárhelyen (Târgu Mureș). Az Argento Group tagja.',
        'company.tagline' => 'Gyengén savas galvanikus horganyzás függesztve és dobban',
        'seo.default_description' => 'Gyengén savas galvanikus horganyzás Marosvásárhelyen: függesztett alkatrészek 1900 × 1200 × 450 mm-ig, kis alkatrészek dobban. Kérjen ajánlatot.',
    ],
    'source' => 'elgarom.ro (korábbi weboldal), letöltve 2026.11.06., Technológia oldal',
    'menu' => [
        'header' => [['Szolgáltatások', 'servicii'], ['Folyamat', 'procesul-de-zincare'], ['Műszaki adatok', 'capacitati-tehnice'], ['Alkalmazások', 'aplicatii'], ['Projektek', 'proiecte'], ['Rólunk', 'despre'], ['Kapcsolat', 'contact'], ['Ajánlatkérés', 'solicita-oferta', true]],
        'footer' => [['Minőség és dokumentumok', 'calitate'], ['Gyakori kérdések', 'intrebari-frecvente'], ['Ajánlatkérés', 'solicita-oferta'], ['Adatvédelmi tájékoztató', 'politica-de-confidentialitate'], ['Sütiszabályzat', 'politica-cookies']],
    ],
    'pages' => [
        'acasa' => ['slug' => '/', 'nav' => 'Főoldal', 'title' => 'Az acél átalakítása. Tartós védelem.',
            'lead' => 'Gyengén savas galvanikus horganyzás acél alkatrészekre, teljesen automatizált soron Marosvásárhelyen. Nagy alkatrészek függesztve, kis alkatrészek dobban.',
            'meta_title' => 'Galvanikus horganyzás Romániában | ELGAROM',
            'meta_description' => 'Gyengén savas galvanikus horganyzás függesztve (1900 × 1200 × 450 mm-ig) és dobban (100 kg-ig). Automatizált sor, saját labor. Kérjen ajánlatot.',
            'sections' => [
                ['scene', ['items' => [
                    ['title' => 'Az acél alkatrész a függesztőkereten', 'text' => 'A nagy alkatrészeket függesztőkeretre akasztjuk, a kis alkatrészeket dobokba töltjük.'],
                    ['title' => 'Szabályozott leengedés a fürdőbe', 'text' => 'Az automatikus szállítóhíd kézi beavatkozás nélkül viszi végig az alkatrészeket a sor fürdőin.'],
                    ['title' => 'Galvanikus cinkleválasztás', 'text' => 'A gyengén savas fürdőben a cink az alkatrész felületére válik ki. A paramétereket elektronikusan felügyeljük.'],
                    ['title' => 'Egyenletes cinkréteg', 'text' => 'Az alkatrész a teljes felületen szabályozottan leválasztott, folytonos cinkréteggel kerül ki.'],
                    ['title' => 'Passziválás a jobb védelemért', 'text' => 'A passziválás és – kis alkatrészeknél – a top coat növeli a korrózióállóságot.'],
                ], 'note' => 'Egyszerűsített szemléltetés. A valós folyamat több fürdőt és lépést tartalmaz, ezeket a Horganyzási folyamat oldal írja le.']],
                ['features', ['heading' => 'Miért választják az ipari ügyfelek az Elgaromot', 'intro' => 'Horganyzósor, amelyet több mint 40 éves szakmai tapasztalattal rendelkező európai partnerekkel együtt terveztünk és építettünk.', 'items' => [
                    ['title' => 'Azonos minőség a teljes tételben', 'text' => 'A sor teljesen automatizált: a folyamatlépéseket a rendszer hangolja össze, a kézi beavatkozás minimális.'],
                    ['title' => 'Szabályozott rétegleválasztás', 'text' => 'A technológiai paramétereket elektronikusan felügyeljük, hogy az alkatrész teljes felülete megfeleljen a követelményeknek.'],
                    ['title' => 'Nagy és kis alkatrészek', 'text' => 'Függesztve: alkatrészek 1900 × 1200 × 450 mm-ig. Dobban: töltetenként legfeljebb 100 kg kis alkatrész.'],
                    ['title' => 'Saját labor', 'text' => 'Elemezzük az aktív fürdőket, és meghatározzuk a leválasztott cinkréteg vastagságát.'],
                    ['title' => 'Kiegészítő védelem', 'text' => 'Horganyzás utáni passziválás és top coat (lezárás) kis alkatrészekre, külön centrifugasoron.'],
                    ['title' => 'Kezelt szennyvíz', 'text' => 'Automatikus szennyvízkezelő állomás semlegesíti a technológiai szennyvizet a környezetvédelmi előírások szerint.'],
                ]]],
                ['envelope', ['heading' => 'Mi fér a sorra', 'intro' => 'Egy függesztett alkatrész legnagyobb befoglaló mérete és egy dob legnagyobb töltete.']],
                ['photo_band', ['image' => 'img:linia-zincare-bai', 'caption' => 'Az Elgarom galvanikus horganyzósora, Marosvásárhely']],
                ['services', ['heading' => 'Szolgáltatások', 'intro' => 'Egyedi és sorozatgyártású, kis és nagy alkatrészek horganyzása számos iparág számára.']],
                $durability,
                $group('Az Argento Group tagja'),
                ['quote_short', ['heading' => 'Írjon nekünk röviden', 'intro' => 'Részletes ajánlathoz használja a teljes űrlapot. Gyors kérdéshez elég egy üzenet.']],
            ]],
        'despre' => ['slug' => 'rolunk', 'nav' => 'Rólunk', 'title' => 'Az Elgaromról',
            'lead' => 'Galvanikus horganyzási szolgáltató automatizált sorral Marosvásárhelyen és saját műszaki csapattal.',
            'meta_title' => 'Az Elgarom és az Argento Group | ELGAROM', 'meta_description' => 'Az ELGAROM automatizált, gyengén savas galvanikus horganyzósort üzemeltet Marosvásárhelyen, és az Argento Group tagja.',
            'sections' => [
                ['intro', ['heading' => 'Pontosságra épített horganyzósor', 'body' => "Az ELGAROM Románia egyik legkorszerűbb galvanikus horganyzósorát üzemelteti, amelyet neves európai partnerekkel közösen terveztünk és építettünk.\n\nA teljes horganyzási folyamat automatizált. Összetett rendszerek alkotják, felügyelik és hangolják össze a sor működését, amely gyengén savas galvanikus horganyzást végezhet függesztve és dobban is.", 'image' => 'img:hala-receptie-elgarom', 'image_side' => 'right']],
                ['features', ['heading' => 'Hogyan dolgozunk', 'items' => [
                    ['title' => 'Megoldás minden alkatrészre', 'text' => 'Ügyfeleink termékeihez bevonatolási megoldásokat fejlesztünk, szoros párbeszédben velük.'],
                    ['title' => 'Műszaki szolgáltatás', 'text' => 'Szakembereink az anyagok felületkezelésének minden kérdésében segítenek.'],
                    ['title' => 'Kis és nagy sorozatok', 'text' => 'Egyedi és sorozatgyártású, kis és nagy alkatrészeket vonunk be számos iparág számára.'],
                    ['title' => 'Felelős gyártás', 'text' => 'A környezetbarát gyártás minden folyamatunkban vállalati filozófiánk része. A szennyvizet automatikusan kezeljük.'],
                ]]],
                ['text', ['heading' => 'Minőség és innováció', 'body' => "Minden folyamatunkban állandó, reprodukálható felületi minőségre és pontosságra törekszünk. Az automatizált folyamatlépések és a minőségbiztosítási rendszerek megbízhatóságot és pontos felületi minőséget biztosítanak.\n\nFontos számunkra az innovatív gondolkodás és a jövőorientáltság. Folyamattechnológiánk ügyfélre szabott alkalmazásával és fejlesztésével tartósan szeretnénk hozzájárulni ügyfeleink sikeréhez."]],
                $group('Argento Group'),
                ['gallery', ['heading' => 'Az Elgarom képekben', 'images' => ['img:receptie-elgarom', 'img:linia-zincare-pod-transportor', 'img:laborator-chimic', 'img:statie-epurare', 'img:sala-sedinte', 'img:birouri-coridor']]],
                $cta,
            ]],
        'servicii' => ['slug' => 'szolgaltatasok', 'nav' => 'Szolgáltatások', 'title' => 'Horganyzási szolgáltatások',
            'lead' => 'Gyengén savas galvanikus horganyzás függesztve és dobban, utána passziválás és – kis alkatrészeknél – top coat.',
            'meta_title' => 'Galvanikus horganyzási szolgáltatások | ELGAROM', 'meta_description' => 'Függesztéses horganyzás (1900 × 1200 × 450 mm-ig), dobos horganyzás (100 kg-ig), passziválás és top coat, laborellenőrzés.',
            'sections' => [
                ['services', ['heading' => 'Amit végzünk', 'intro' => '']],
                ['features', ['heading' => 'Anyagok', 'intro' => 'Az alábbi acélcsoportokhoz rendelkezünk megfelelő eljárással. A pontos eljárást az alkatrész vizsgálata után erősítjük meg.', 'items' => [
                    ['title' => 'Alacsony széntartalmú szénacél', 'text' => ''], ['title' => 'Közepes széntartalmú szénacél', 'text' => ''], ['title' => 'Magas széntartalmú szénacél', 'text' => ''],
                    ['title' => 'Rozsdamentes acélok', 'text' => ''], ['title' => 'Ötvözött és erősen ötvözött acélok', 'text' => ''],
                ]]],
                $cta,
            ]],
        'procesul-de-zincare' => ['slug' => 'horganyzasi-folyamat', 'nav' => 'Folyamat', 'title' => 'A horganyzási folyamat',
            'lead' => 'Hogyan kap egy nyers acél alkatrész passzivált cinkréteget az Elgarom automatizált során.',
            'meta_title' => 'A galvanikus horganyzás folyamata | ELGAROM', 'meta_description' => 'A gyengén savas galvanikus horganyzás lépései az Elgaromnál: alkatrész-vizsgálat, függesztés vagy dobba töltés, horganyzás, passziválás, top coat és laborellenőrzés.',
            'sections' => [
                ['steps', ['heading' => 'A lépések röviden', 'intro' => 'A főoldali szemléltetés csak a horganyzás pillanatát mutatja. A soron egy alkatrész több lépésen megy át:', 'items' => [
                    ['title' => 'Az alkatrész és a követelmények vizsgálata', 'text' => 'A műszaki csapat értékeli az anyagot, a geometriát és a védelmi követelményeket, és meghatározza a megfelelő eljárást.'],
                    ['title' => 'Függesztés vagy dobba töltés', 'text' => 'A nagy alkatrészeket függesztőkeretre akasztjuk (legnagyobb befoglaló méret 1900 × 1200 × 450 mm). A kis alkatrészek ömlesztve kerülnek a dobokba (töltetenként legfeljebb 100 kg).'],
                    ['title' => 'Gyengén savas galvanikus horganyzás', 'text' => 'Az automatikus szállítóhíd végigviszi az alkatrészeket a fürdőkön. A technológiai paramétereket elektronikusan felügyeljük a teljes felületen szabályozott leválasztás érdekében. A fürdők fényesítő adalékrendszert tartalmaznak.'],
                    ['title' => 'Passziválás', 'text' => 'Horganyzás után az alkatrészeket passziváló oldatokkal kezeljük a jobb korrózióállóság érdekében.'],
                    ['title' => 'Top coat kis alkatrészekre', 'text' => 'A horganyzott és passzivált kis alkatrészek lezárhatók: bemerítés az oldatba, lecsepegtetés, centrifugálás és szárítás (polimerizáció).'],
                    ['title' => 'Laborellenőrzés', 'text' => 'A labor elemzi az aktív fürdők összetevőinek koncentrációját, és meghatározza a leválasztott cinkréteg vastagságát.'],
                ], 'note' => 'A közbenső fürdőket és a pontos sorrendet minden alkatrésztípusra a műszaki csapat határozza meg.']],
                ['photo_band', ['image' => 'img:linia-zincare-pod-transportor', 'caption' => 'A horganyzósor automatikus szállítóhídja']],
                ['intro', ['heading' => 'Ezzel párhuzamosan: a szennyvíz kezelése', 'body' => 'A szennyvízkezelő és semlegesítő állomás automatikusan kezeli a folyamat során keletkező savas és lúgos szennyvizet. A folyamatszámítógép elektronikus felügyelete biztosítja a környezetre káros anyagok eltávolítását, így a kibocsátott víz megfelel a hatályos környezetvédelmi előírásoknak. A labor kémiai és spektrofotometriás vizsgálatokkal ellenőrzi a kezelt vizet.', 'image' => 'img:statie-epurare', 'image_side' => 'left']],
                $durability,
                $cta,
            ]],
        'capacitati-tehnice' => ['slug' => 'muszaki-adatok', 'nav' => 'Műszaki adatok', 'title' => 'Műszaki adatok',
            'lead' => 'Az Elgarom horganyzósorának megerősített adatai. Más értékekre műszaki csapatunk közvetlenül válaszol.',
            'meta_title' => 'A horganyzósor műszaki adatai | ELGAROM', 'meta_description' => 'Legnagyobb függesztési befoglaló méret 1900 × 1200 × 450 mm, dobtöltet 100 kg, automatizált sor, saját labor, szennyvízkezelő állomás.',
            'sections' => [
                ['envelope', ['heading' => 'Befoglaló méret és töltet', 'intro' => '']],
                ['capabilities', ['heading' => 'Megerősített műszaki adatok', 'intro' => 'Csak megerősített értékeket közlünk. Rétegvastagságokról, passziválási típusokról vagy egyéb követelményekről kérdezze műszaki csapatunkat.', 'group' => '']],
                ['intro', ['heading' => 'A galvanikus horganyzósor', 'body' => 'A sort több mint 40 éves szakmai tapasztalattal rendelkező, neves európai partnerekkel közösen terveztük és építettük. A teljes folyamat automatizált; a berendezések működését alkotó, felügyelő és összehangoló rendszerek minimálisra csökkentik a kézi beavatkozást.', 'image' => 'img:linia-zincare-dispozitive', 'image_side' => 'right']],
                ['intro', ['heading' => 'A vegyi labor', 'body' => "A labor felszereltsége lehetővé teszi:\n\n- az aktív fürdők vegyi összetevőinek koncentrációelemzését;\n- a kezelt és kibocsátott víz kémiai és spektrofotometriás vizsgálatát;\n- a leválasztott cinkréteg vastagságának meghatározását.", 'image' => 'img:laborator-chimic', 'image_side' => 'left']],
                ['intro', ['heading' => 'Centrifugasor a top coathoz', 'body' => 'A horganyzott és passzivált kis alkatrészeket külön soron zárjuk le: bemerítés a lezáró oldatba, lecsepegtetés, centrifugálás és szárítás (polimerizáció).', 'image' => 'img:centrifuge-top-coating', 'image_side' => 'right']],
                $cta,
            ]],
        'aplicatii' => ['slug' => 'alkalmazasok', 'nav' => 'Alkalmazások', 'title' => 'Alkalmazások és iparágak',
            'lead' => 'Egyedi és sorozatgyártású, kis és nagy alkatrészeket horganyzunk számos iparág számára.',
            'meta_title' => 'A galvanikus horganyzás alkalmazásai | ELGAROM', 'meta_description' => 'Kis sorozatalkatrészek, sajtolt alkatrészek, részegységek és nagy acél alkatrészek – függesztve vagy dobban horganyozva az Elgaromnál.',
            'sections' => [
                ['industries', ['heading' => 'Kiszolgált iparágak', 'intro' => '']],
                ['features', ['heading' => 'Milyen alkatrészek', 'intro' => 'Az eljárást az alkatrész mérete és formája szerint választjuk.', 'items' => [
                    ['title' => 'Kis alkatrészek ömlesztve', 'text' => 'Kötőelemek, sajtolt alkatrészek és más kis sorozatelemek dobban horganyozva – töltetenként 100 kg-ig.'],
                    ['title' => 'Nagy alkatrészek vagy összetett geometria', 'text' => 'Részegységek, megmunkált alkatrészek és látható felületű alkatrészek függesztve horganyozva – 1900 × 1200 × 450 mm-ig.'],
                ]]],
                $cta,
            ]],
        'proiecte' => ['slug' => 'projektek', 'nav' => 'Projektek', 'title' => 'Projektek és galéria',
            'lead' => 'Az Elgarom létesítményei és – ügyfeleink hozzájárulásával – megvalósított projektek.',
            'meta_title' => 'Projektek és fotógaléria | ELGAROM', 'meta_description' => 'Fotók az Elgarom marosvásárhelyi horganyzósoráról, laborjáról és szennyvízkezelő állomásáról.',
            'sections' => [
                ['projects', ['heading' => 'Projektek', 'intro' => '']],
                ['gallery', ['heading' => 'Fotógaléria', 'intro' => 'A horganyzósor, a labor és a szennyvízkezelő állomás Marosvásárhelyen.', 'images' => ['img:linia-zincare-bai', 'img:linia-zincare-pod-transportor', 'img:linia-zincare-dispozitive', 'img:linia-zincare-panorama', 'img:centrifuge-top-coating', 'img:laborator-chimic', 'img:laborator-analiza', 'img:statie-epurare', 'img:hala-receptie-elgarom']]],
                $cta,
            ]],
        'calitate' => ['slug' => 'minoseg', 'nav' => 'Minőség', 'title' => 'Minőség és dokumentumok',
            'lead' => 'Automatizált folyamat, felügyelt paraméterek és laborellenőrzés.',
            'meta_title' => 'Minőség és dokumentumok | ELGAROM', 'meta_description' => 'Minőségellenőrzés az Elgaromnál: automatizált folyamat, a paraméterek elektronikus felügyelete, saját vegyi labor.',
            'sections' => [
                ['intro', ['heading' => 'Reprodukálható minőség', 'body' => "Az automatizált folyamatlépések és a technológiai paraméterek elektronikus felügyelete tételről tételre állandó felületi minőséget biztosít.\n\nSaját laborunkban ellenőrizzük az aktív fürdők koncentrációját és a leválasztott cinkréteg vastagságát.", 'image' => 'img:laborator-analiza', 'image_side' => 'right']],
                ['documents', ['heading' => 'Dokumentumok', 'intro' => 'Letölthető tanúsítványok és műszaki dokumentumok.', 'category' => '']],
                $cta,
            ]],
        'intrebari-frecvente' => ['slug' => 'gyik', 'nav' => 'GYIK', 'title' => 'Gyakori kérdések',
            'lead' => 'Rövid válaszok a horganyzásról, méretekről, anyagokról és az ajánlatkérésről.',
            'meta_title' => 'Gyakori kérdések a horganyzásról | ELGAROM', 'meta_description' => 'Milyen horganyzást végez az Elgarom, legnagyobb méretek, anyagok, utókezelések, minőség és hogyan kérhet ajánlatot.',
            'sections' => [['faq', ['heading' => '', 'category' => '']], $cta]],
        'solicita-oferta' => ['slug' => 'ajanlatkeres', 'nav' => 'Ajánlat', 'title' => 'Ajánlatkérés',
            'lead' => 'Írja le, milyen alkatrésze van. Az ismeretlen mezőket üresen hagyhatja.',
            'meta_title' => 'Horganyzási ajánlat kérése | ELGAROM', 'meta_description' => 'Ajánlatkérés galvanikus horganyzásra: írja le az alkatrészt és a mennyiséget, töltsön fel rajzokat vagy fotókat.',
            'sections' => []],
        'contact' => ['slug' => 'kapcsolat', 'nav' => 'Kapcsolat', 'title' => 'Kapcsolat',
            'lead' => 'Értékesítés, műszaki támogatás és a marosvásárhelyi horganyzósor címe.',
            'meta_title' => 'Kapcsolat | ELGAROM Marosvásárhely', 'meta_description' => 'ELGAROM, Str. Barajului nr. 3, Marosvásárhely (Târgu Mureș). Értékesítés +40 752 144 291, műszaki +40 748 118 339, vanzari@elgarom.ro.',
            'sections' => [
                ['contact', ['heading' => 'Elérhetőségek', 'intro' => '']],
                ['quote_short', ['heading' => 'Üzenet küldése', 'intro' => 'Munkanapokon válaszolunk az üzenetekre.']],
            ]],
        'politica-de-confidentialitate' => ['slug' => 'adatvedelem', 'nav' => null, 'title' => 'Adatvédelmi tájékoztató', 'lead' => null,
            'meta_title' => 'Adatvédelmi tájékoztató | ELGAROM', 'meta_description' => 'Hogyan kezeli az ELGAROM a weboldalon keresztül megadott személyes adatokat.',
            'sections' => [['text', ['heading' => '', 'body' => <<<'MD'
Ez a tájékoztató leírja, milyen személyes adatokat kezelünk az elgarom.ro weboldal használatakor, miért, és milyen jogai vannak. Az adatkezelő: {{company}}, székhelye: {{address}}. Elérhetőségünk: {{email}}.

## Milyen adatokat kezelünk

- **Ajánlatkérések és üzenetek:** név, cég, e-mail, telefon, település, az alkatrészek leírása és a feltöltött fájlok (rajzok, fotók).
- **MI-asszisztens:** a beszélgetésben írt üzenetek, az asszisztens ablakában jelzett ideig.
- **Technikai adatok:** IP-cím (visszaélések elleni védelem céljából álnevesítve), böngésző típusa.
- **Statisztika és marketing:** csak ha a sütisávban hozzájárul.

## Miért kezeljük

- kérése megválaszolása és ajánlat készítése érdekében (szerződéskötést megelőző lépések, GDPR 6. cikk (1) b));
- a weboldal biztonsága és a visszaélések megelőzése érdekében (jogos érdek, GDPR 6. cikk (1) f));
- marketingtájékoztatás céljából csak akkor, ha ehhez külön hozzájárult (hozzájárulás, GDPR 6. cikk (1) a)).

## Meddig őrizzük

Az ajánlatkéréseket az ajánlatadáshoz és az üzleti kapcsolathoz szükséges ideig, azt követően a jogszabályi kötelezettségek szerint őrizzük. Az MI-asszisztenssel folytatott beszélgetések a beállított idő után automatikusan törlődnek. Marketing-hozzájárulását bármikor visszavonhatja.

## Kinek továbbítjuk

Az adatokhoz csak az Elgarom jogosult munkatársai férnek hozzá. A tárhelyhez és az e-mailhez szolgáltatókat veszünk igénybe. Ha az MI-asszisztenst használja, az üzeneteket az MI-szolgáltatás nyújtója dolgozza fel. Statisztikai adatokat csak az Ön hozzájárulásával továbbítunk a Google-nek.

## Az Ön jogai

Önt megilleti a hozzáférés, helyesbítés, törlés, korlátozás, adathordozhatóság és tiltakozás joga, valamint a hozzájárulás visszavonásának joga. Ezek gyakorlásához írjon nekünk: {{email}}. Panaszt tehet a romániai adatvédelmi felügyeleti hatóságnál (ANSPDCP, www.dataprotection.ro).
MD]]]],
        'politica-cookies' => ['slug' => 'suti-szabalyzat', 'nav' => null, 'title' => 'Sütiszabályzat', 'lead' => null,
            'meta_title' => 'Sütiszabályzat | ELGAROM', 'meta_description' => 'Milyen sütiket használ az ELGAROM weboldala, és hogyan módosíthatja beállításait.',
            'sections' => [['text', ['heading' => '', 'body' => <<<'MD'
A weboldal a működéshez feltétlenül szükséges sütiket, valamint – csak az Ön hozzájárulásával – statisztikai és marketingsütiket használ.

## Kategóriák

- **Feltétlenül szükséges:** munkamenet, űrlapvédelem (CSRF) és a sütiválasztás megjegyzése. Nem kapcsolhatók ki.
- **Statisztika (Google Analytics):** megmutatja, hogyan használják a weboldalt. Csak hozzájárulás után aktiválódik.
- **Marketing (Google Ads):** a kampányok hatékonyságát méri. Csak hozzájárulás után aktiválódik.

Választása előtt nem töltődnek be a Google statisztikai vagy marketingcímkéi. Ha visszavonja hozzájárulását, a weboldal által beállított Google-sütik törlődnek.

A Kapcsolat oldal Google-térképe csak akkor töltődik be, ha a „Térkép megjelenítése” gombra kattint.

## Beállítások módosítása

Használja a lábléc „Sütibeállítások” hivatkozását. A sütiket bármikor törölheti a böngésző beállításaiban.
MD]]]],
    ],
    'services' => [
        'zincare-pe-dispozitive' => ['slug' => 'fuggesztett-horganyzas', 'title' => 'Galvanikus függesztéses horganyzás',
            'summary' => 'Nagyobb alkatrészekhez és részegységekhez, amelyeket egyenként függesztőkeretre akasztunk. Legnagyobb befoglaló méret: 1900 × 1200 × 450 mm.',
            'body' => "Az alkatrészeket függesztőkeretekre szereljük, és automatikusan haladnak végig a sor fürdőin. Minden alkatrész szabályozottan van kitéve a folyamatnak – összetett geometriájú, látható felületű vagy nagy méretű alkatrészekhez ideális.\n\n- Legnagyobb befoglaló méret függesztve: **1900 × 1200 × 450 mm**\n- Gyengén savas galvanikus horganyzás fényesítő adalékokkal\n- Passziválás a nagyobb korrózióállóságért\n\nKüldje el az alkatrész rajzát vagy fényképét, és megmondjuk, hogyan rögzíthető a függesztőkereten."],
        'zincare-in-tambur' => ['slug' => 'dobos-horganyzas', 'title' => 'Galvanikus dobos horganyzás',
            'summary' => 'Ömlesztett kis alkatrészekhez – kötőelemek, sajtolt alkatrészek, sorozatelemek. Töltetenként legfeljebb 100 kg alkatrész.',
            'body' => "A kis alkatrészeket ömlesztve, forgó dobokban horganyozzuk, amelyek automatikusan haladnak végig a sor fürdőin. A forgás biztosítja, hogy a töltet minden alkatrészének minden felülete bevonatot kapjon.\n\n- Legnagyobb töltet: dobonként **100 kg** alkatrész\n- Kis alkatrészek nagy sorozataihoz\n- Opcionálisan: top coat (lezárás) a centrifugasoron\n\nAjánlathoz adja meg az alkatrész típusát, anyagát és a becsült mennyiséget."],
        'pasivare-si-top-coating' => ['slug' => 'passzivalas-es-top-coat', 'title' => 'Passziválás és top coat',
            'summary' => 'Horganyzás után alkalmazott kezelések a jobb korrózióállóságért. A kis alkatrészek lezárása külön centrifugasoron történik.',
            'body' => "A cinkréteg leválasztása után az alkatrészeket passziváljuk. A horganyzott és passzivált kis alkatrészeken az Elgarom kiegészítő felületlezárást (top coat) is végez.\n\nA lezárás négy lépésből áll: az alkatrészek bemerítése az oldatba, lecsepegtetés, centrifugálás és a felvitt réteg szárítása (polimerizációja).\n\nA passziválás típusát és a top coat szükségességét a műszaki csapattal együtt, az alkatrész követelményei szerint határozzuk meg."],
        'consultanta-tehnica-si-laborator' => ['slug' => 'muszaki-tanacsadas-es-labor', 'title' => 'Műszaki tanácsadás és laborellenőrzés',
            'summary' => 'Ajánlatadás előtt megvizsgáljuk az alkatrész követelményeit, a laborban pedig ellenőrizzük az aktív fürdőket és a leválasztott rétegvastagságot.',
            'body' => "Minden alkatrésznek megvannak a saját követelményei. A műszaki csapat értékeli az anyagot, a geometriát és a felhasználási körülményeket, és Önnel egyeztetve javasolja a megfelelő eljárást.\n\nAz Elgarom vegyi laborja elvégzi:\n\n- az aktív fürdők összetevőinek koncentrációelemzését;\n- a leválasztott cinkréteg vastagságának meghatározását;\n- a kezelt víz kémiai és spektrofotometriás vizsgálatát.\n\nMűszaki kérdésekben: **+40 748 118 339**."],
    ],
    'faqs' => [
        ['proces', 'Milyen horganyzást végeznek?', 'Gyengén savas galvanikus horganyzást, függesztve (akasztott alkatrészek) és dobban (ömlesztett kis alkatrészek). A galvanikus horganyzás más eljárás, mint a tűzihorganyzás (olvadt cinkbe mártás).'],
        ['capacitati', 'Mekkorák lehetnek legfeljebb az alkatrészek?', 'Függesztve legfeljebb 1900 × 1200 × 450 mm-es alkatrészeket horganyozhatunk. Dobban a legnagyobb töltet 100 kg kis alkatrész. Ha az alkatrész e határok közelében van, küldje el a rajzát ellenőrzésre.'],
        ['materiale', 'Milyen anyagokat tudnak horganyozni?', 'Rendelkezünk eljárással alacsony, közepes és magas széntartalmú szénacélokra, rozsdamentes acélokra, valamint ötvözött és erősen ötvözött acélokra. A megfelelő eljárást az alkatrész vizsgálata után erősítjük meg.'],
        ['proces', 'Milyen utókezeléseket alkalmaznak?', 'Horganyzás után az alkatrészeket passziváljuk. Kis alkatrészekre a centrifugasoron top coat (lezárás) is felvihető a jobb korrózióállóság érdekében.'],
        ['calitate', 'Hogyan ellenőrzik a réteg minőségét?', 'A folyamatparamétereket a soron elektronikusan felügyeljük. Saját laborunkban elemezzük az aktív fürdőket, és meghatározzuk a leválasztott cinkréteg vastagságát.'],
        ['oferta', 'Milyen adatok kellenek az ajánlathoz?', 'Segít az alkatrész típusa, anyaga, mennyisége, méretei és egy rajz vagy fénykép. Ha nem ismer minden adatot, küldje el, amije van – a csapat felveszi Önnel a kapcsolatot a részletekért.'],
        ['oferta', 'Küldhetek műszaki rajzokat?', 'Igen. Az ajánlatkérő űrlapon legfeljebb 6 fájlt tölthet fel: PDF-et, képeket (JPG, PNG, WebP) vagy CAD-fájlokat (DXF, DWG, STEP, IGES), egyenként legfeljebb 15 MB méretben. A fájlokhoz csak az Elgarom csapata fér hozzá.'],
        ['oferta', 'Mennyibe kerül és mennyi ideig tart a horganyzás?', 'Az ár és a határidő az alkatrésztől, a mennyiségtől és a követelményektől függ, ezért vizsgálat nélkül nem becsülhető. Küldjön ajánlatkérést, és a csapat konkrét javaslattal válaszol.'],
        ['durabilitate', 'Hány évig tartanak a horganyzott alkatrészek?', 'A védelem időtartama a rétegvastagságtól, a passziválástól és a top coattól, de leginkább a felhasználási környezettől függ. Általános értéket években nem adunk meg: az Ön alkalmazásának konkrét követelményeit egyeztetjük.'],
        ['mediu', 'Hogyan kezelik a technológiai szennyvizet?', 'A savas és lúgos szennyvizet automatikus szennyvízkezelő és semlegesítő állomáson kezeljük, így a kibocsátott víz megfelel a hatályos környezetvédelmi előírásoknak.'],
    ],
    'capabilities' => [
        'process' => ['label' => 'Eljárás', 'value' => 'Gyengén savas galvanikus horganyzás'],
        'execution' => ['label' => 'Kivitelezés', 'value' => 'Függesztve és dobban'],
        'rack_envelope' => ['label' => 'Legnagyobb befoglaló méret függesztve'],
        'barrel_load' => ['label' => 'Legnagyobb dobtöltet'],
        'automation' => ['label' => 'Automatizálás', 'value' => 'Teljesen automatizált sor, elektronikusan felügyelt paraméterek'],
        'post' => ['label' => 'Utókezelések', 'value' => 'Passziválás; top coat (lezárás) kis alkatrészekre'],
        'lab' => ['label' => 'Laborellenőrzés', 'value' => 'Az aktív fürdők elemzése, a cinkréteg vastagságának meghatározása'],
        'water' => ['label' => 'Szennyvíz', 'value' => 'Automatikus kezelő és semlegesítő állomás'],
        'thickness' => ['label' => 'Elérhető rétegvastagságok'],
        'passivation_types' => ['label' => 'Passziválási típusok'],
        'salt_spray' => ['label' => 'Sóködállóság (vizsgálati módszerrel)'],
        'rack_weight' => ['label' => 'Legnagyobb tömeg függesztőkeretenként'],
        'barrel_part_size' => ['label' => 'Legnagyobb alkatrészméret dobban'],
        'tanks' => ['label' => 'Fürdőméretek'],
        'throughput' => ['label' => 'Gyártási kapacitás'],
    ],
    'industries' => ['Gép- és berendezésgyártás', 'Autóipar – alkatrészek', 'Építőipar és kötőelemek', 'Elektromos berendezések', 'Bútor és vasalatok'],
    'media' => [
        'linia-zincare-bai' => 'Az Elgarom galvanikus horganyzósora – technológiai kádak és kezelőjárda',
        'linia-zincare-pod-transportor' => 'Automatikus szállítódaru a horganyzósor kádjai felett',
        'linia-zincare-dispozitive' => 'Felülnézet a horganyzósorra a szállítókeretekkel',
        'linia-zincare-panorama' => 'A galvanikus horganyzósor kádjainak panorámája',
        'statie-epurare' => 'Szennyvíztisztító és -semlegesítő állomás',
        'centrifuge-top-coating' => 'Centrifugális berendezések kis alkatrészek top coat bevonatához',
        'laborator-chimic' => 'Az Elgarom vegyi laboratóriuma',
        'laborator-analiza' => 'Vegyi elemzés az Elgarom laboratóriumában',
        'hala-receptie-elgarom' => 'Az Elgarom recepciója, kilátással a gyártócsarnokra',
        'receptie-elgarom' => 'Bejárat az Elgarom csarnokába',
        'sala-sedinte' => 'Az Elgarom tárgyalóterme',
        'birouri-coridor' => 'Az Elgarom irodai része',
    ],
];
