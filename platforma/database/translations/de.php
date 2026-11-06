<?php

// Deutsche Inhalte (übersetzt aus dem Rumänischen; vor der Veröffentlichung von einem Muttersprachler prüfen lassen).
// 'img:<name>' verweist auf Bilder der Mediathek, 'page:<schlüssel>' auf eine Seite in dieser Sprache.

$cta = ['cta', [
    'heading' => 'Haben Sie Teile zum Verzinken?',
    'text' => 'Senden Sie uns, was Sie über das Teil wissen – für den Anfang genügt ein Foto. Das technische Team antwortet Ihnen mit einem Vorschlag.',
    'button_label' => 'Angebot anfordern', 'button_url' => 'page:solicita-oferta',
    'secondary_label' => 'Vertrieb anrufen', 'secondary_url' => 'tel:+40752144291',
]];
$durability = ['durability', [
    'heading' => 'Wie lange der Schutz hält',
    'intro' => 'Die Zinkschicht schützt Stahl auf zwei Arten: als Barriere zwischen Metall und Umgebung und dadurch, dass das Zink an Stelle des Stahls korrodiert. Wie lange sie hält, hängt von einigen konkreten Faktoren ab.',
    'items' => [
        ['title' => 'Schichtdicke', 'text' => 'Eine dickere Schicht wird langsamer abgetragen. Die Dicke wird nach den Anforderungen des Teils festgelegt und im Labor geprüft.'],
        ['title' => 'Passivierung', 'text' => 'Die Passivierung nach dem Verzinken verlangsamt die Korrosion der Zinkschicht.'],
        ['title' => 'Topcoat', 'text' => 'Die zusätzliche Versiegelung, verfügbar für Kleinteile, erhöht die Korrosionsbeständigkeit.'],
        ['title' => 'Einsatzumgebung', 'text' => 'Feuchtigkeit, Salz, Chemikalien und Kondenswasser beschleunigen die Korrosion. Dasselbe Teil hält innen anders als außen.'],
    ],
    'note' => 'Die Grafik ist eine qualitative Darstellung, keine Garantie. Die Schutzdauer für Ihre Anwendung besprechen wir anhand der konkreten Anforderungen.',
]];
$group = fn ($heading) => ['group', [
    'heading' => $heading,
    'body' => 'ELGAROM gehört zur Argento Group, gemeinsam mit Unternehmen wie Fepo und Euronarcis.',
    'companies' => [
        ['name' => 'Argento Group', 'url' => 'https://argento-group.com/', 'text' => ''],
        ['name' => 'Fepo', 'url' => 'https://fepo.ro/', 'text' => ''],
        ['name' => 'Euronarcis', 'url' => 'https://www.euronarcis.com/', 'text' => ''],
    ],
]];

return [
    'settings' => [
        'footer.text' => 'ELGAROM führt schwach saure galvanische Verzinkung auf einer automatisierten Linie in Târgu Mureș (Rumänien) aus. Ein Unternehmen der Argento Group.',
        'company.tagline' => 'Schwach saure galvanische Verzinkung im Gestell und in der Trommel',
        'seo.default_description' => 'Galvanische Verzinkung (schwach sauer) in Târgu Mureș, Rumänien: Gestellteile bis 1900 × 1200 × 450 mm und Kleinteile in der Trommel. Jetzt Angebot anfordern.',
    ],
    'source' => 'elgarom.ro (frühere Website), abgerufen am 06.11.2026, Seite Technologie',
    'menu' => [
        'header' => [['Leistungen', 'servicii'], ['Verfahren', 'procesul-de-zincare'], ['Technische Daten', 'capacitati-tehnice'], ['Anwendungen', 'aplicatii'], ['Projekte', 'proiecte'], ['Über uns', 'despre'], ['Kontakt', 'contact'], ['Angebot anfordern', 'solicita-oferta', true]],
        'footer' => [['Qualität und Dokumente', 'calitate'], ['Häufige Fragen', 'intrebari-frecvente'], ['Angebot anfordern', 'solicita-oferta'], ['Datenschutzerklärung', 'politica-de-confidentialitate'], ['Cookie-Richtlinie', 'politica-cookies']],
    ],
    'pages' => [
        'acasa' => ['slug' => '/', 'nav' => 'Startseite', 'title' => 'Stahl verwandeln. Schutz, der hält.',
            'lead' => 'Schwach saure galvanische Verzinkung für Stahlteile auf einer vollautomatischen Linie in Târgu Mureș. Große Teile im Gestell, Kleinteile in der Trommel.',
            'meta_title' => 'Galvanische Verzinkung in Rumänien | ELGAROM',
            'meta_description' => 'Schwach saure galvanische Verzinkung im Gestell (bis 1900 × 1200 × 450 mm) und in der Trommel (bis 100 kg). Automatisierte Linie, eigenes Labor. Angebot anfordern.',
            'sections' => [
                ['scene', ['items' => [
                    ['title' => 'Das Stahlteil, vorbereitet im Gestell', 'text' => 'Große Teile werden ins Gestell gehängt, Kleinteile in Trommeln gefüllt.'],
                    ['title' => 'Kontrolliertes Absenken ins Bad', 'text' => 'Die automatische Transportbrücke führt die Teile ohne manuellen Eingriff durch die Bäder der Linie.'],
                    ['title' => 'Galvanische Zinkabscheidung', 'text' => 'Im schwach sauren Bad scheidet sich Zink auf der Oberfläche ab. Die Parameter werden elektronisch überwacht.'],
                    ['title' => 'Gleichmäßige Zinkschicht', 'text' => 'Das Teil verlässt das Bad mit einer durchgehenden, kontrolliert abgeschiedenen Zinkschicht auf der gesamten Oberfläche.'],
                    ['title' => 'Passivierung für besseren Schutz', 'text' => 'Passivierung und – für Kleinteile – Topcoat erhöhen die Korrosionsbeständigkeit.'],
                ], 'note' => 'Vereinfachte Darstellung. Der reale Prozess umfasst mehrere Bäder und Schritte, die auf der Seite „Verzinkungsprozess“ beschrieben sind.']],
                ['features', ['heading' => 'Warum Industriekunden Elgarom wählen', 'intro' => 'Eine Verzinkungslinie, geplant und gebaut mit europäischen Partnern mit über 40 Jahren Erfahrung in der Branche.', 'items' => [
                    ['title' => 'Gleiche Qualität im ganzen Los', 'text' => 'Die Linie ist vollautomatisch: Die Prozessschritte werden vom System koordiniert, manuelle Eingriffe sind auf ein Minimum reduziert.'],
                    ['title' => 'Kontrolliert abgeschiedene Schicht', 'text' => 'Die technologischen Parameter werden elektronisch überwacht, damit die gesamte Oberfläche des Teils den Anforderungen entspricht.'],
                    ['title' => 'Große und kleine Teile', 'text' => 'Im Gestell: Teile bis 1900 × 1200 × 450 mm. In der Trommel: bis 100 kg Kleinteile pro Charge.'],
                    ['title' => 'Eigenes Labor', 'text' => 'Wir analysieren die aktiven Bäder und bestimmen die abgeschiedene Zinkschichtdicke.'],
                    ['title' => 'Zusätzlicher Schutz', 'text' => 'Passivierung nach dem Verzinken und Topcoat (Versiegelung) für Kleinteile auf einer eigenen Zentrifugenlinie.'],
                    ['title' => 'Behandeltes Abwasser', 'text' => 'Eine automatische Abwasserbehandlungsanlage neutralisiert das Prozessabwasser gemäß den Umweltvorschriften.'],
                ]]],
                ['envelope', ['heading' => 'Was auf die Linie passt', 'intro' => 'Maximale Teileabmessung im Gestell und maximale Trommelbeladung.']],
                ['photo_band', ['image' => 'img:linia-zincare-bai', 'caption' => 'Galvanische Verzinkungslinie von Elgarom, Târgu Mureș']],
                ['services', ['heading' => 'Leistungen', 'intro' => 'Verzinkung von Einzel- und Serienteilen, klein und groß, für unterschiedliche Branchen.']],
                $durability,
                $group('Teil der Argento Group'),
                ['quote_short', ['heading' => 'Schreiben Sie uns kurz', 'intro' => 'Für ein ausführliches Angebot nutzen Sie das vollständige Formular. Für eine kurze Frage genügt eine Nachricht.']],
            ]],
        'despre' => ['slug' => 'ueber-uns', 'nav' => 'Über uns', 'title' => 'Über Elgarom',
            'lead' => 'Dienstleister für galvanische Verzinkung mit einer automatisierten Linie in Târgu Mureș und einem eigenen technischen Team.',
            'meta_title' => 'Über Elgarom und die Argento Group | ELGAROM', 'meta_description' => 'ELGAROM betreibt in Târgu Mureș eine automatisierte Linie für schwach saure galvanische Verzinkung und gehört zur Argento Group.',
            'sections' => [
                ['intro', ['heading' => 'Eine Verzinkungslinie für Präzision', 'body' => "ELGAROM betreibt eine der leistungsfähigsten galvanischen Verzinkungslinien Rumäniens, geplant und gebaut mit renommierten europäischen Partnern.\n\nDer gesamte Verzinkungsprozess ist automatisiert. Komplexe Systeme steuern, überwachen und koordinieren den Betrieb der Linie, die schwach saure galvanische Verzinkung sowohl im Gestell als auch in der Trommel ausführen kann.", 'image' => 'img:hala-receptie-elgarom', 'image_side' => 'right']],
                ['features', ['heading' => 'So arbeiten wir', 'items' => [
                    ['title' => 'Lösungen für jedes Teil', 'text' => 'Wir entwickeln Beschichtungslösungen für die Produkte unserer Kunden – im engen Dialog mit ihnen.'],
                    ['title' => 'Technischer Service', 'text' => 'Unser Expertenteam unterstützt Sie bei allen Fragen der Oberflächenbehandlung.'],
                    ['title' => 'Kleine und große Serien', 'text' => 'Wir beschichten Einzel- und Serienteile, klein und groß, für Branchen aus unterschiedlichen Bereichen.'],
                    ['title' => 'Verantwortungsvolle Produktion', 'text' => 'Umweltgerechte Produktion in allen Prozessen ist Teil unserer Unternehmensphilosophie. Das Abwasser wird automatisch behandelt.'],
                ]]],
                ['text', ['heading' => 'Qualität und Innovation', 'body' => "In allen Prozessen streben wir eine gleichbleibende, reproduzierbare Qualität und Präzision der Oberfläche an. Automatisierte Prozessabläufe und Qualitätssicherungssysteme sorgen für Zuverlässigkeit und eine exakte Oberflächenqualität.\n\nInnovatives Denken und Zukunftsorientierung sind uns wichtig. Durch die kundenspezifische Anwendung und Weiterentwicklung unserer Verfahrenstechnik möchten wir nachhaltig zum Erfolg unserer Kunden beitragen."]],
                $group('Argento Group'),
                ['gallery', ['heading' => 'Elgarom in Bildern', 'images' => ['img:receptie-elgarom', 'img:linia-zincare-pod-transportor', 'img:laborator-chimic', 'img:statie-epurare', 'img:sala-sedinte', 'img:birouri-coridor']]],
                $cta,
            ]],
        'servicii' => ['slug' => 'leistungen', 'nav' => 'Leistungen', 'title' => 'Verzinkungsleistungen',
            'lead' => 'Schwach saure galvanische Verzinkung im Gestell und in der Trommel, gefolgt von Passivierung und – für Kleinteile – Topcoat.',
            'meta_title' => 'Leistungen: galvanische Verzinkung | ELGAROM', 'meta_description' => 'Gestellverzinkung (bis 1900 × 1200 × 450 mm), Trommelverzinkung (bis 100 kg), Passivierung und Topcoat, Laborkontrolle.',
            'sections' => [
                ['services', ['heading' => 'Was wir ausführen', 'intro' => '']],
                ['features', ['heading' => 'Werkstoffe', 'intro' => 'Für folgende Stahlgruppen verfügen wir über geeignete Verfahren. Das genaue Verfahren bestätigen wir nach Prüfung des Teils.', 'items' => [
                    ['title' => 'Kohlenstoffstahl mit niedrigem Kohlenstoffgehalt', 'text' => ''], ['title' => 'Kohlenstoffstahl mit mittlerem Kohlenstoffgehalt', 'text' => ''],
                    ['title' => 'Kohlenstoffstahl mit hohem Kohlenstoffgehalt', 'text' => ''], ['title' => 'Nichtrostende Stähle', 'text' => ''], ['title' => 'Legierte und hochlegierte Stähle', 'text' => ''],
                ]]],
                $cta,
            ]],
        'procesul-de-zincare' => ['slug' => 'verzinkungsprozess', 'nav' => 'Verfahren', 'title' => 'Der Verzinkungsprozess',
            'lead' => 'Wie ein Stahlteil auf der automatisierten Elgarom-Linie zu einer passivierten Zinkschicht kommt.',
            'meta_title' => 'Prozess der galvanischen Verzinkung | ELGAROM', 'meta_description' => 'Die Schritte der schwach sauren galvanischen Verzinkung bei Elgarom: Prüfung des Teils, Gestell oder Trommel, Verzinken, Passivieren, Topcoat und Laborkontrolle.',
            'sections' => [
                ['steps', ['heading' => 'Die Schritte im Überblick', 'intro' => 'Die Darstellung auf der Startseite zeigt nur den Moment des Verzinkens. Auf der Linie durchläuft ein Teil mehrere Schritte:', 'items' => [
                    ['title' => 'Prüfung von Teil und Anforderungen', 'text' => 'Das technische Team bewertet Werkstoff, Geometrie und Schutzanforderungen und legt das passende Verfahren fest.'],
                    ['title' => 'Aufhängen im Gestell oder Befüllen der Trommel', 'text' => 'Große Teile werden ins Gestell gehängt (max. 1900 × 1200 × 450 mm). Kleinteile kommen als Schüttgut in Trommeln (max. 100 kg pro Charge).'],
                    ['title' => 'Schwach saure galvanische Verzinkung', 'text' => 'Die automatische Transportbrücke führt die Teile durch die Bäder. Die technologischen Parameter werden elektronisch überwacht – für eine kontrollierte Schichtabscheidung auf der gesamten Oberfläche. Die Bäder enthalten ein Glanzzusatzsystem.'],
                    ['title' => 'Passivierung', 'text' => 'Nach dem Verzinken werden die Teile mit Passivierungslösungen behandelt, um die Korrosionsbeständigkeit zu verbessern.'],
                    ['title' => 'Topcoat für Kleinteile', 'text' => 'Verzinkte und passivierte Kleinteile können versiegelt werden: Tauchen in die Lösung, Abtropfen, Zentrifugieren und Trocknen (Polymerisation).'],
                    ['title' => 'Laborkontrolle', 'text' => 'Das Labor analysiert die Konzentration der Badbestandteile und bestimmt die abgeschiedene Zinkschichtdicke.'],
                ], 'note' => 'Zwischenbäder und genaue Reihenfolge legt das technische Team für jede Teileart fest.']],
                ['photo_band', ['image' => 'img:linia-zincare-pod-transportor', 'caption' => 'Automatische Transportbrücke der Verzinkungslinie']],
                ['intro', ['heading' => 'Parallel dazu: Abwasserbehandlung', 'body' => 'Die Abwasserbehandlungs- und Neutralisationsanlage behandelt das saure und alkalische Prozessabwasser automatisch. Die elektronische Überwachung durch den Prozessrechner sorgt dafür, dass umweltschädliche Stoffe entfernt werden und das eingeleitete Wasser den geltenden Umweltvorschriften entspricht. Das Labor prüft das behandelte Wasser chemisch und spektralphotometrisch.', 'image' => 'img:statie-epurare', 'image_side' => 'left']],
                $durability,
                $cta,
            ]],
        'capacitati-tehnice' => ['slug' => 'technische-daten', 'nav' => 'Technische Daten', 'title' => 'Technische Daten',
            'lead' => 'Die bestätigten Daten der Elgarom-Verzinkungslinie. Für weitere Werte antwortet Ihnen das technische Team direkt.',
            'meta_title' => 'Technische Daten der Verzinkungslinie | ELGAROM', 'meta_description' => 'Max. Teileabmessung im Gestell 1900 × 1200 × 450 mm, Trommelbeladung 100 kg, automatisierte Linie, eigenes Labor, Abwasserbehandlung.',
            'sections' => [
                ['envelope', ['heading' => 'Abmessungen und Beladung', 'intro' => '']],
                ['capabilities', ['heading' => 'Bestätigte technische Daten', 'intro' => 'Wir zeigen nur bestätigte Werte. Zu Schichtdicken, Passivierungsarten oder weiteren Anforderungen fragen Sie bitte das technische Team.', 'group' => '']],
                ['intro', ['heading' => 'Die galvanische Verzinkungslinie', 'body' => 'Die Linie wurde gemeinsam mit renommierten europäischen Partnern mit über 40 Jahren Branchenerfahrung geplant und gebaut. Der gesamte Prozess ist automatisiert; die Systeme, die den Betrieb der Anlagen steuern, überwachen und koordinieren, reduzieren manuelle Eingriffe auf ein Minimum.', 'image' => 'img:linia-zincare-dispozitive', 'image_side' => 'right']],
                ['intro', ['heading' => 'Das Chemielabor', 'body' => "Das Labor ist ausgestattet für:\n\n- Analysen der Konzentration der chemischen Bestandteile der aktiven Bäder;\n- chemische und spektralphotometrische Analysen des behandelten und eingeleiteten Wassers;\n- Bestimmungen der abgeschiedenen Zinkschichtdicke.", 'image' => 'img:laborator-chimic', 'image_side' => 'left']],
                ['intro', ['heading' => 'Zentrifugenlinie für Topcoat', 'body' => 'Verzinkte und passivierte Kleinteile werden auf einer eigenen Linie versiegelt: Tauchen in die Versiegelungslösung, Abtropfen, Zentrifugieren und Trocknen (Polymerisation).', 'image' => 'img:centrifuge-top-coating', 'image_side' => 'right']],
                $cta,
            ]],
        'aplicatii' => ['slug' => 'anwendungen', 'nav' => 'Anwendungen', 'title' => 'Anwendungen und Branchen',
            'lead' => 'Wir verzinken Einzel- und Serienteile, klein und groß, für Branchen aus unterschiedlichen Bereichen.',
            'meta_title' => 'Anwendungen der galvanischen Verzinkung | ELGAROM', 'meta_description' => 'Kleinteile in Serie, Stanzteile, Baugruppen und große Stahlteile – bei Elgarom im Gestell oder in der Trommel verzinkt.',
            'sections' => [
                ['industries', ['heading' => 'Branchen', 'intro' => '']],
                ['features', ['heading' => 'Welche Teile', 'intro' => 'Das Verfahren richtet sich nach Größe und Form des Teils.', 'items' => [
                    ['title' => 'Kleinteile als Schüttgut', 'text' => 'Verbindungselemente, Stanzteile und andere kleine Serienteile werden in Trommeln verzinkt – bis 100 kg pro Charge.'],
                    ['title' => 'Große Teile oder komplexe Geometrie', 'text' => 'Baugruppen, bearbeitete Teile und Teile mit Sichtflächen werden im Gestell verzinkt – bis 1900 × 1200 × 450 mm.'],
                ]]],
                $cta,
            ]],
        'proiecte' => ['slug' => 'projekte', 'nav' => 'Projekte', 'title' => 'Projekte und Galerie',
            'lead' => 'Die Anlagen von Elgarom und – mit Zustimmung der Kunden – realisierte Projekte.',
            'meta_title' => 'Projekte und Fotogalerie | ELGAROM', 'meta_description' => 'Fotos der Verzinkungslinie, des Labors und der Abwasserbehandlungsanlage von Elgarom in Târgu Mureș.',
            'sections' => [
                ['projects', ['heading' => 'Projekte', 'intro' => '']],
                ['gallery', ['heading' => 'Fotogalerie', 'intro' => 'Verzinkungslinie, Labor und Abwasserbehandlung in Târgu Mureș.', 'images' => ['img:linia-zincare-bai', 'img:linia-zincare-pod-transportor', 'img:linia-zincare-dispozitive', 'img:linia-zincare-panorama', 'img:centrifuge-top-coating', 'img:laborator-chimic', 'img:laborator-analiza', 'img:statie-epurare', 'img:hala-receptie-elgarom']]],
                $cta,
            ]],
        'calitate' => ['slug' => 'qualitaet', 'nav' => 'Qualität', 'title' => 'Qualität und Dokumente',
            'lead' => 'Automatisierter Prozess, überwachte Parameter und Kontrolle im Labor.',
            'meta_title' => 'Qualität und Dokumente | ELGAROM', 'meta_description' => 'Qualitätskontrolle bei Elgarom: automatisierter Prozess, elektronische Parameterüberwachung, eigenes Chemielabor.',
            'sections' => [
                ['intro', ['heading' => 'Reproduzierbare Qualität', 'body' => "Automatisierte Prozessabläufe und die elektronische Überwachung der technologischen Parameter sorgen für eine gleichbleibende Oberflächenqualität von Los zu Los.\n\nIm eigenen Labor prüfen wir die Konzentration der aktiven Bäder und die abgeschiedene Zinkschichtdicke.", 'image' => 'img:laborator-analiza', 'image_side' => 'right']],
                ['documents', ['heading' => 'Dokumente', 'intro' => 'Zertifikate und technische Dokumente zum Herunterladen.', 'category' => '']],
                $cta,
            ]],
        'intrebari-frecvente' => ['slug' => 'faq', 'nav' => 'FAQ', 'title' => 'Häufige Fragen',
            'lead' => 'Kurze Antworten zu Verzinkung, Abmessungen, Werkstoffen und Angebot.',
            'meta_title' => 'Häufige Fragen zur Verzinkung | ELGAROM', 'meta_description' => 'Welche Verzinkung Elgarom ausführt, maximale Abmessungen, Werkstoffe, Nachbehandlung, Qualität und wie Sie ein Angebot anfordern.',
            'sections' => [['faq', ['heading' => '', 'category' => '']], $cta]],
        'solicita-oferta' => ['slug' => 'angebot-anfordern', 'nav' => 'Angebot', 'title' => 'Angebot anfordern',
            'lead' => 'Beschreiben Sie Ihr Teil. Felder, die Sie nicht kennen, können leer bleiben.',
            'meta_title' => 'Angebot für Verzinkung anfordern | ELGAROM', 'meta_description' => 'Angebotsanfrage für galvanische Verzinkung: Teil, Menge beschreiben und Zeichnungen oder Fotos hochladen.',
            'sections' => []],
        'contact' => ['slug' => 'kontakt', 'nav' => 'Kontakt', 'title' => 'Kontakt',
            'lead' => 'Vertrieb, technischer Support und Adresse der Verzinkungslinie in Târgu Mureș.',
            'meta_title' => 'Kontakt | ELGAROM Târgu Mureș', 'meta_description' => 'ELGAROM, Str. Barajului Nr. 3, Târgu Mureș, Rumänien. Vertrieb +40 752 144 291, Technik +40 748 118 339, vanzari@elgarom.ro.',
            'sections' => [
                ['contact', ['heading' => 'Kontaktdaten', 'intro' => '']],
                ['quote_short', ['heading' => 'Nachricht senden', 'intro' => 'Wir beantworten Nachrichten an Werktagen.']],
            ]],
        'politica-de-confidentialitate' => ['slug' => 'datenschutz', 'nav' => null, 'title' => 'Datenschutzerklärung', 'lead' => null,
            'meta_title' => 'Datenschutzerklärung | ELGAROM', 'meta_description' => 'Wie ELGAROM die über die Website übermittelten personenbezogenen Daten verarbeitet.',
            'sections' => [['text', ['heading' => '', 'body' => <<<'MD'
Diese Erklärung beschreibt, welche personenbezogenen Daten wir bei der Nutzung der Website elgarom.ro verarbeiten, warum und welche Rechte Sie haben. Verantwortlicher ist {{company}}, mit Sitz in {{address}}. Sie erreichen uns unter {{email}}.

## Welche Daten wir verarbeiten

- **Angebotsanfragen und Nachrichten:** Name, Unternehmen, E-Mail, Telefon, Ort, Beschreibung der Teile und hochgeladene Dateien (Zeichnungen, Fotos).
- **KI-Assistent:** die im Gespräch geschriebenen Nachrichten, gespeichert für den im Assistentenfenster angegebenen Zeitraum.
- **Technische Daten:** IP-Adresse (pseudonymisiert zum Schutz vor Missbrauch), Browsertyp.
- **Statistik und Marketing:** nur wenn Sie im Cookie-Banner zustimmen.

## Warum wir sie verarbeiten

- um Ihre Anfrage zu beantworten und ein Angebot zu erstellen (vorvertragliche Maßnahmen, Art. 6 Abs. 1 lit. b DSGVO);
- für die Sicherheit der Website und die Verhinderung von Missbrauch (berechtigtes Interesse, Art. 6 Abs. 1 lit. f DSGVO);
- für Werbeinformationen nur, wenn Sie gesondert eingewilligt haben (Einwilligung, Art. 6 Abs. 1 lit. a DSGVO).

## Wie lange wir sie speichern

Angebotsanfragen werden so lange gespeichert, wie es für das Angebot und die Geschäftsbeziehung erforderlich ist, danach gemäß den gesetzlichen Pflichten. Gespräche mit dem KI-Assistenten werden nach dem eingestellten Zeitraum automatisch gelöscht. Sie können Ihre Marketing-Einwilligung jederzeit widerrufen.

## An wen wir sie übermitteln

Die Daten sind nur befugten Mitarbeitern von Elgarom zugänglich. Für Hosting und E-Mail nutzen wir Dienstleister. Wenn Sie den KI-Assistenten nutzen, werden die Nachrichten vom Anbieter des KI-Dienstes verarbeitet. Statistikdaten werden nur mit Ihrer Einwilligung an Google übermittelt.

## Ihre Rechte

Sie haben das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung, Datenübertragbarkeit und Widerspruch sowie das Recht, Ihre Einwilligung zu widerrufen. Schreiben Sie uns dazu an {{email}}. Sie können sich bei der rumänischen Datenschutzaufsichtsbehörde (ANSPDCP, www.dataprotection.ro) beschweren.
MD]]]],
        'politica-cookies' => ['slug' => 'cookie-richtlinie', 'nav' => null, 'title' => 'Cookie-Richtlinie', 'lead' => null,
            'meta_title' => 'Cookie-Richtlinie | ELGAROM', 'meta_description' => 'Welche Cookies die ELGAROM-Website verwendet und wie Sie Ihre Einstellungen ändern.',
            'sections' => [['text', ['heading' => '', 'body' => <<<'MD'
Die Website verwendet technisch notwendige Cookies und – nur mit Ihrer Einwilligung – Statistik- und Marketing-Cookies.

## Kategorien

- **Technisch notwendig:** Sitzung, Formularschutz (CSRF) und Speicherung Ihrer Cookie-Auswahl. Sie können nicht deaktiviert werden.
- **Statistik (Google Analytics):** zeigt uns, wie die Website genutzt wird. Wird nur nach Einwilligung aktiviert.
- **Marketing (Google Ads):** misst die Wirksamkeit von Kampagnen. Wird nur nach Einwilligung aktiviert.

Vor Ihrer Auswahl werden keine Statistik- oder Marketing-Tags von Google geladen. Wenn Sie Ihre Einwilligung widerrufen, werden die von der Website gesetzten Google-Cookies gelöscht.

Die Google-Karte auf der Kontaktseite wird erst geladen, wenn Sie auf „Karte anzeigen“ klicken.

## Einstellungen ändern

Nutzen Sie den Link „Cookie-Einstellungen“ in der Fußzeile. Cookies können Sie jederzeit in den Browsereinstellungen löschen.
MD]]]],
    ],
    'services' => [
        'zincare-pe-dispozitive' => ['slug' => 'gestellverzinkung', 'title' => 'Galvanische Gestellverzinkung',
            'summary' => 'Für größere Teile und Baugruppen, die einzeln ins Gestell gehängt werden. Maximale Abmessung: 1900 × 1200 × 450 mm.',
            'body' => "Die Teile werden in Gestelle gehängt und durchlaufen automatisch die Bäder der Linie. Jedes Teil wird kontrolliert dem Prozess ausgesetzt – geeignet für Teile mit komplexer Geometrie, Sichtflächen oder großen Abmessungen.\n\n- Maximale Abmessung im Gestell: **1900 × 1200 × 450 mm**\n- Schwach saure galvanische Verzinkung mit Glanzzusätzen\n- Passivierung für höhere Korrosionsbeständigkeit\n\nSenden Sie uns die Zeichnung oder ein Foto des Teils, und wir sagen Ihnen, wie es im Gestell befestigt werden kann."],
        'zincare-in-tambur' => ['slug' => 'trommelverzinkung', 'title' => 'Galvanische Trommelverzinkung',
            'summary' => 'Für Kleinteile als Schüttgut – Verbindungselemente, Stanzteile, Serienkomponenten. Bis 100 kg Teile pro Charge.',
            'body' => "Kleinteile werden als Schüttgut in rotierenden Trommeln verzinkt, die automatisch durch die Bäder der Linie laufen. Die Rotation sorgt dafür, dass alle Flächen der Teile beschichtet werden.\n\n- Maximale Beladung: **100 kg** Teile pro Trommel\n- Geeignet für große Serien kleiner Teile\n- Optional: Topcoat (Versiegelung) auf der Zentrifugenlinie\n\nFür ein Angebot nennen Sie bitte Teileart, Werkstoff und geschätzte Menge."],
        'pasivare-si-top-coating' => ['slug' => 'passivierung-und-topcoat', 'title' => 'Passivierung und Topcoat',
            'summary' => 'Nachbehandlungen nach dem Verzinken für eine bessere Korrosionsbeständigkeit. Die Versiegelung von Kleinteilen erfolgt auf einer eigenen Zentrifugenlinie.',
            'body' => "Nach dem Abscheiden der Zinkschicht werden die Teile passiviert. Verzinkte und passivierte Kleinteile erhalten bei Elgarom eine zusätzliche Oberflächenversiegelung (Topcoat).\n\nDie Versiegelung erfolgt in vier Schritten: Tauchen der Teile in die Lösung, Abtropfen, Zentrifugieren und Trocknen (Polymerisation) der aufgetragenen Schicht.\n\nPassivierungsart und Notwendigkeit eines Topcoats werden gemeinsam mit dem technischen Team nach den Anforderungen des Teils festgelegt."],
        'consultanta-tehnica-si-laborator' => ['slug' => 'technische-beratung-und-labor', 'title' => 'Technische Beratung und Laborkontrolle',
            'summary' => 'Wir prüfen die Anforderungen an das Teil vor dem Angebot und kontrollieren im Labor die aktiven Bäder und die abgeschiedene Schichtdicke.',
            'body' => "Jedes Teil hat seine eigenen Anforderungen. Das technische Team bewertet Werkstoff, Geometrie und Einsatzbedingungen und schlägt – im Dialog mit Ihnen – das passende Verfahren vor.\n\nDas Chemielabor von Elgarom führt durch:\n\n- Analysen der Konzentration der Bestandteile der aktiven Bäder;\n- Bestimmungen der abgeschiedenen Zinkschichtdicke;\n- chemische und spektralphotometrische Analysen des behandelten Wassers.\n\nFür technische Fragen: **+40 748 118 339**."],
    ],
    'faqs' => [
        ['proces', 'Welche Art von Verzinkung führen Sie aus?', 'Schwach saure galvanische Verzinkung, im Gestell (hängende Teile) und in der Trommel (Kleinteile als Schüttgut). Die galvanische Verzinkung ist ein anderes Verfahren als die Feuerverzinkung (Tauchen in flüssiges Zink).'],
        ['capacitati', 'Welche Teileabmessungen sind maximal möglich?', 'Im Gestell können wir Teile bis 1900 × 1200 × 450 mm verzinken. In der Trommel beträgt die maximale Beladung 100 kg Kleinteile. Für Teile an diesen Grenzen senden Sie uns bitte die Zeichnung zur Prüfung.'],
        ['materiale', 'Welche Werkstoffe können Sie verzinken?', 'Wir verfügen über Verfahren für Kohlenstoffstahl mit niedrigem, mittlerem und hohem Kohlenstoffgehalt, nichtrostende Stähle sowie legierte und hochlegierte Stähle. Das passende Verfahren bestätigen wir nach Prüfung des Teils.'],
        ['proces', 'Welche Nachbehandlungen bieten Sie an?', 'Die Teile werden nach dem Verzinken passiviert. Kleinteile können zusätzlich auf der Zentrifugenlinie mit einem Topcoat (Versiegelung) versehen werden, für eine bessere Korrosionsbeständigkeit.'],
        ['calitate', 'Wie prüfen Sie die Schichtqualität?', 'Die Prozessparameter werden auf der Linie elektronisch überwacht. In unserem Labor analysieren wir die aktiven Bäder und bestimmen die abgeschiedene Zinkschichtdicke.'],
        ['oferta', 'Welche Angaben brauchen Sie für ein Angebot?', 'Hilfreich sind Teileart, Werkstoff, Menge, Abmessungen und eine Zeichnung oder ein Foto. Wenn Sie nicht alle Angaben kennen, senden Sie, was Sie haben – das Team kontaktiert Sie für Details.'],
        ['oferta', 'Kann ich technische Zeichnungen senden?', 'Ja. Im Angebotsformular können Sie bis zu 6 Dateien hochladen: PDF, Bilder (JPG, PNG, WebP) oder CAD-Dateien (DXF, DWG, STEP, IGES), jeweils bis 15 MB. Die Dateien sind nur für das Elgarom-Team zugänglich.'],
        ['oferta', 'Was kostet das Verzinken und wie lange dauert es?', 'Preis und Lieferzeit hängen von Teil, Menge und Anforderungen ab und lassen sich ohne Prüfung nicht schätzen. Senden Sie eine Angebotsanfrage, und das Team antwortet mit einem konkreten Vorschlag.'],
        ['durabilitate', 'Wie viele Jahre halten verzinkte Teile?', 'Die Schutzdauer hängt von der Schichtdicke, von Passivierung und Topcoat und vor allem von der Umgebung ab, in der das Teil eingesetzt wird. Einen allgemeinen Wert in Jahren nennen wir nicht: Wir besprechen die konkreten Anforderungen Ihrer Anwendung.'],
        ['mediu', 'Wie behandeln Sie das Prozessabwasser?', 'Saures und alkalisches Abwasser wird in einer automatischen Abwasserbehandlungs- und Neutralisationsanlage behandelt, sodass das eingeleitete Wasser den geltenden Umweltvorschriften entspricht.'],
    ],
    'capabilities' => [
        'process' => ['label' => 'Verfahren', 'value' => 'Galvanische Verzinkung, schwach sauer'],
        'execution' => ['label' => 'Ausführung', 'value' => 'Im Gestell und in der Trommel'],
        'rack_envelope' => ['label' => 'Max. Teileabmessung im Gestell'],
        'barrel_load' => ['label' => 'Max. Trommelbeladung'],
        'automation' => ['label' => 'Automatisierung', 'value' => 'Vollautomatische Linie, elektronisch überwachte Parameter'],
        'post' => ['label' => 'Nachbehandlung', 'value' => 'Passivierung; Topcoat (Versiegelung) für Kleinteile'],
        'lab' => ['label' => 'Laborkontrolle', 'value' => 'Analyse der aktiven Bäder, Bestimmung der Zinkschichtdicke'],
        'water' => ['label' => 'Abwasser', 'value' => 'Automatische Behandlungs- und Neutralisationsanlage'],
        'thickness' => ['label' => 'Verfügbare Schichtdicken'],
        'passivation_types' => ['label' => 'Passivierungsarten'],
        'salt_spray' => ['label' => 'Salzsprühnebelbeständigkeit (mit Prüfmethode)'],
        'rack_weight' => ['label' => 'Max. Gewicht pro Gestell'],
        'barrel_part_size' => ['label' => 'Max. Teilegröße in der Trommel'],
        'tanks' => ['label' => 'Badabmessungen'],
        'throughput' => ['label' => 'Produktionskapazität'],
    ],
    'industries' => ['Maschinen- und Anlagenbau', 'Automobilindustrie – Komponenten', 'Bauwesen und Verbindungselemente', 'Elektrische Ausrüstung', 'Möbel und Beschläge'],
    'media' => [
        'linia-zincare-bai' => 'Galvanische Verzinkungslinie von Elgarom – Prozessbäder und Bedienlaufsteg',
        'linia-zincare-pod-transportor' => 'Automatischer Transportkran über den Bädern der Verzinkungslinie',
        'linia-zincare-dispozitive' => 'Draufsicht auf die Verzinkungslinie mit den Transportgestellen',
        'linia-zincare-panorama' => 'Panorama der Bäder der galvanischen Verzinkungslinie',
        'statie-epurare' => 'Kläranlage zur Behandlung und Neutralisation des Abwassers',
        'centrifuge-top-coating' => 'Zentrifugenanlagen für Topcoat bei Kleinteilen',
        'laborator-chimic' => 'Chemisches Labor von Elgarom',
        'laborator-analiza' => 'Chemische Analyse im Elgarom-Labor',
        'hala-receptie-elgarom' => 'Empfang von Elgarom mit Blick in die Produktionshalle',
        'receptie-elgarom' => 'Eingang zur Halle von Elgarom',
        'sala-sedinte' => 'Besprechungsraum von Elgarom',
        'birouri-coridor' => 'Bürobereich von Elgarom',
    ],
];
