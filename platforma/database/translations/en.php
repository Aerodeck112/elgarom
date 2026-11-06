<?php

// English content (translated from Romanian; have it reviewed by a native speaker before relying on it).

$cta = ['cta', [
    'heading' => 'Do you have parts to be zinc plated?',
    'text' => 'Send us what you know about the part – a photo is enough to start with. Our technical team will reply with a proposal.',
    'button_label' => 'Request a quote', 'button_url' => 'page:solicita-oferta',
    'secondary_label' => 'Call sales', 'secondary_url' => 'tel:+40752144291',
]];
$durability = ['durability', [
    'heading' => 'How long the protection lasts',
    'intro' => 'A zinc layer protects steel in two ways: as a barrier between the metal and its environment, and by corroding in place of the steel. How long it lasts depends on a few concrete factors.',
    'items' => [
        ['title' => 'Layer thickness', 'text' => 'A thicker layer is consumed more slowly. The thickness is set according to the part requirements and checked in the laboratory.'],
        ['title' => 'Passivation', 'text' => 'Passivation applied after plating slows down the corrosion of the zinc layer.'],
        ['title' => 'Top coat', 'text' => 'Additional sealing, available for small parts, increases corrosion resistance.'],
        ['title' => 'Service environment', 'text' => 'Humidity, salt, chemicals and condensation accelerate corrosion. The same part lasts differently indoors and outdoors.'],
    ],
    'note' => 'The graphic is a qualitative illustration, not a guarantee. We discuss the protection period for your application based on its concrete requirements.',
]];
$group = fn ($heading) => ['group', [
    'heading' => $heading,
    'body' => 'ELGAROM is part of Argento Group, together with companies such as Fepo and Euronarcis.',
    'companies' => [
        ['name' => 'Argento Group', 'url' => 'https://argento-group.com/', 'text' => ''],
        ['name' => 'Fepo', 'url' => 'https://fepo.ro/', 'text' => ''],
        ['name' => 'Euronarcis', 'url' => 'https://www.euronarcis.com/', 'text' => ''],
    ],
]];

return [
    'settings' => [
        'footer.text' => 'ELGAROM performs weak-acid electrolytic zinc plating on an automated line in Târgu Mureș, Romania. Part of Argento Group.',
        'company.tagline' => 'Weak-acid electrolytic zinc plating, rack and barrel',
        'seo.default_description' => 'Weak-acid electrolytic zinc plating in Târgu Mureș, Romania: rack parts up to 1900 × 1200 × 450 mm and small parts in barrels. Request a quote.',
    ],
    'source' => 'elgarom.ro (previous website), retrieved 06.11.2026, Technology page',
    'menu' => [
        'header' => [['Services', 'servicii'], ['Process', 'procesul-de-zincare'], ['Capabilities', 'capacitati-tehnice'], ['Applications', 'aplicatii'], ['Projects', 'proiecte'], ['About', 'despre'], ['Contact', 'contact'], ['Request a quote', 'solicita-oferta', true]],
        'footer' => [['Quality and documents', 'calitate'], ['FAQ', 'intrebari-frecvente'], ['Request a quote', 'solicita-oferta'], ['Privacy policy', 'politica-de-confidentialitate'], ['Cookie policy', 'politica-cookies']],
    ],
    'pages' => [
        'acasa' => ['slug' => '/', 'nav' => 'Home', 'title' => 'Transforming steel. Protection built to last.',
            'lead' => 'Weak-acid electrolytic zinc plating for steel parts on a fully automated line in Târgu Mureș. Large parts on racks, small parts in barrels.',
            'meta_title' => 'Electrolytic zinc plating in Romania | ELGAROM',
            'meta_description' => 'Weak-acid electrolytic zinc plating on racks (up to 1900 × 1200 × 450 mm) and in barrels (up to 100 kg). Automated line, in-house laboratory. Request a quote.',
            'sections' => [
                ['scene', ['items' => [
                    ['title' => 'The steel part, prepared on the rack', 'text' => 'Large parts are hung on racks; small parts are loaded into barrels.'],
                    ['title' => 'Controlled lowering into the bath', 'text' => 'The automatic transport gantry moves the parts through the baths of the line, without manual handling.'],
                    ['title' => 'Electrolytic zinc deposition', 'text' => 'In the weak-acid bath, zinc is deposited on the surface of the part. The parameters are monitored electronically.'],
                    ['title' => 'A uniform zinc layer', 'text' => 'The part comes out with a continuous zinc layer, deposited in a controlled way over the whole surface.'],
                    ['title' => 'Passivation for better protection', 'text' => 'Passivation and, for small parts, a top coat increase corrosion resistance.'],
                ], 'note' => 'Simplified illustration. The real process includes several baths and steps, described on the zinc plating process page.']],
                ['features', ['heading' => 'Why industrial customers choose Elgarom', 'intro' => 'A plating line designed and built together with European partners with more than 40 years of experience in the field.', 'items' => [
                    ['title' => 'The same quality across the batch', 'text' => 'The line is fully automated: process sequences are coordinated by the system and manual intervention is kept to a minimum.'],
                    ['title' => 'Controlled layer deposition', 'text' => 'Process parameters are monitored electronically, so that the whole surface of the part meets the requirements.'],
                    ['title' => 'Large parts and small parts', 'text' => 'On racks: parts up to 1900 × 1200 × 450 mm. In barrels: up to 100 kg of small parts per load.'],
                    ['title' => 'In-house laboratory', 'text' => 'We analyse the active baths and measure the thickness of the deposited zinc layer.'],
                    ['title' => 'Additional protection', 'text' => 'Passivation after plating and a top coat (sealing) for small parts, on a dedicated centrifuge line.'],
                    ['title' => 'Wastewater treated before discharge', 'text' => 'An automatic treatment plant neutralises the process wastewater in line with environmental regulations.'],
                ]]],
                ['envelope', ['heading' => 'What fits on the line', 'intro' => 'Maximum part envelope on a rack and maximum barrel load.']],
                ['photo_band', ['image' => 'img:linia-zincare-bai', 'caption' => 'Elgarom electrolytic zinc plating line, Târgu Mureș']],
                ['services', ['heading' => 'Services', 'intro' => 'Zinc plating for single and series parts, small and large, across many industries.']],
                $durability,
                $group('Part of Argento Group'),
                ['quote_short', ['heading' => 'Send us a short message', 'intro' => 'For a detailed quote, use the full form. For a quick question, a message is enough.']],
            ]],
        'despre' => ['slug' => 'about', 'nav' => 'About', 'title' => 'About Elgarom',
            'lead' => 'A zinc plating service provider with an automated line in Târgu Mureș and a dedicated technical team.',
            'meta_title' => 'About Elgarom and Argento Group | ELGAROM', 'meta_description' => 'ELGAROM operates an automated weak-acid electrolytic zinc plating line in Târgu Mureș and is part of Argento Group.',
            'sections' => [
                ['intro', ['heading' => 'A plating line built for precision', 'body' => "ELGAROM operates one of the most efficient electrolytic zinc plating lines in Romania, designed and built together with renowned European partners.\n\nThe entire plating process is automated. Complex systems build, supervise and coordinate the operation of the line, which can perform weak-acid electrolytic zinc plating both on racks and in barrels.", 'image' => 'img:hala-receptie-elgarom', 'image_side' => 'right']],
                ['features', ['heading' => 'How we work', 'items' => [
                    ['title' => 'Solutions for each part', 'text' => 'We develop coating solutions for our customers’ products, in close dialogue with them.'],
                    ['title' => 'Technical service', 'text' => 'Our specialists support you in all matters of surface treatment.'],
                    ['title' => 'Small and large series', 'text' => 'We coat single and series parts, small and large, for industries in many fields.'],
                    ['title' => 'Responsible production', 'text' => 'Environmentally sound production in all our processes is part of our company philosophy. Wastewater is treated automatically.'],
                ]]],
                ['text', ['heading' => 'Quality and innovation', 'body' => "In all our processes we aim for constant, reproducible finishing quality and precision. Automated process sequences and quality assurance systems ensure reliability and an exact surface quality.\n\nInnovative thinking and a focus on the future matter to us. By applying and developing our process technology for each customer, we aim to contribute lastingly to our customers’ success."]],
                $group('Argento Group'),
                ['gallery', ['heading' => 'Elgarom in pictures', 'images' => ['img:receptie-elgarom', 'img:linia-zincare-pod-transportor', 'img:laborator-chimic', 'img:statie-epurare', 'img:sala-sedinte', 'img:birouri-coridor']]],
                $cta,
            ]],
        'servicii' => ['slug' => 'services', 'nav' => 'Services', 'title' => 'Zinc plating services',
            'lead' => 'Weak-acid electrolytic zinc plating on racks and in barrels, followed by passivation and, for small parts, a top coat.',
            'meta_title' => 'Electrolytic zinc plating services | ELGAROM', 'meta_description' => 'Rack plating (up to 1900 × 1200 × 450 mm), barrel plating (up to 100 kg), passivation and top coat, laboratory control.',
            'sections' => [
                ['services', ['heading' => 'What we do', 'intro' => '']],
                ['features', ['heading' => 'Materials', 'intro' => 'We have suitable processes for the following steel groups. We confirm the exact process after reviewing the part.', 'items' => [
                    ['title' => 'Low-carbon steel', 'text' => ''], ['title' => 'Medium-carbon steel', 'text' => ''], ['title' => 'High-carbon steel', 'text' => ''],
                    ['title' => 'Stainless steels', 'text' => ''], ['title' => 'Alloy and high-alloy steels', 'text' => ''],
                ]]],
                $cta,
            ]],
        'procesul-de-zincare' => ['slug' => 'zinc-plating-process', 'nav' => 'Process', 'title' => 'The zinc plating process',
            'lead' => 'How a raw steel part gets a passivated zinc layer on the automated Elgarom line.',
            'meta_title' => 'The electrolytic zinc plating process | ELGAROM', 'meta_description' => 'The steps of weak-acid electrolytic zinc plating at Elgarom: part review, racking or barrel loading, plating, passivation, top coat and laboratory control.',
            'sections' => [
                ['steps', ['heading' => 'The steps at a glance', 'intro' => 'The illustration on the home page shows only the plating moment. On the line, a part goes through several steps:', 'items' => [
                    ['title' => 'Review of the part and its requirements', 'text' => 'The technical team assesses the material, geometry and protection requirements and defines the right process.'],
                    ['title' => 'Racking or barrel loading', 'text' => 'Large parts are hung on racks (maximum envelope 1900 × 1200 × 450 mm). Small parts are loaded in bulk into barrels (up to 100 kg per load).'],
                    ['title' => 'Weak-acid electrolytic zinc plating', 'text' => 'The automatic transport gantry moves the parts through the baths. Process parameters are monitored electronically for a controlled deposition over the whole surface. The baths contain a brightener system.'],
                    ['title' => 'Passivation', 'text' => 'After plating, the parts are treated with passivation solutions for better corrosion resistance.'],
                    ['title' => 'Top coat for small parts', 'text' => 'Plated and passivated small parts can be sealed: immersion in the solution, draining, centrifuging and drying (curing).'],
                    ['title' => 'Laboratory control', 'text' => 'The laboratory analyses the concentration of the active bath components and measures the thickness of the deposited zinc layer.'],
                ], 'note' => 'Intermediate baths and the exact sequence are defined for each type of part by the technical team.']],
                ['photo_band', ['image' => 'img:linia-zincare-pod-transportor', 'caption' => 'Automatic transport gantry of the plating line']],
                ['intro', ['heading' => 'In parallel: wastewater is treated', 'body' => 'The treatment and neutralisation plant automatically treats the acid and alkaline wastewater from the process. Electronic monitoring by the process computer removes substances harmful to the environment, so that the discharged water meets current environmental regulations. The laboratory checks the treated water by chemical and spectrophotometric analysis.', 'image' => 'img:statie-epurare', 'image_side' => 'left']],
                $durability,
                $cta,
            ]],
        'capacitati-tehnice' => ['slug' => 'technical-capabilities', 'nav' => 'Capabilities', 'title' => 'Technical capabilities',
            'lead' => 'The confirmed data of the Elgarom plating line. For other values, our technical team will answer you directly.',
            'meta_title' => 'Technical capabilities of the plating line | ELGAROM', 'meta_description' => 'Maximum rack envelope 1900 × 1200 × 450 mm, barrel load 100 kg, automated line, in-house laboratory, wastewater treatment plant.',
            'sections' => [
                ['envelope', ['heading' => 'Envelope and load', 'intro' => '']],
                ['capabilities', ['heading' => 'Confirmed technical data', 'intro' => 'We only show confirmed values. For layer thicknesses, passivation types or other requirements, please ask our technical team.', 'group' => '']],
                ['intro', ['heading' => 'The electrolytic zinc plating line', 'body' => 'The line was designed and built together with renowned European partners with more than 40 years of experience in the field. The entire process is automated; the systems that build, supervise and coordinate the equipment keep manual intervention to a minimum.', 'image' => 'img:linia-zincare-dispozitive', 'image_side' => 'right']],
                ['intro', ['heading' => 'The chemical laboratory', 'body' => "The laboratory is equipped for:\n\n- analysis of the concentration of the chemical components in the active baths;\n- chemical and spectrophotometric analysis of the treated and discharged water;\n- measurement of the deposited zinc layer thickness.", 'image' => 'img:laborator-chimic', 'image_side' => 'left']],
                ['intro', ['heading' => 'Centrifuge line for top coating', 'body' => 'Plated and passivated small parts are sealed on a dedicated line: immersion in the sealing solution, draining, centrifuging and drying (curing).', 'image' => 'img:centrifuge-top-coating', 'image_side' => 'right']],
                $cta,
            ]],
        'aplicatii' => ['slug' => 'applications', 'nav' => 'Applications', 'title' => 'Applications and industries',
            'lead' => 'We zinc plate single and series parts, small and large, for industries in many fields.',
            'meta_title' => 'Applications of electrolytic zinc plating | ELGAROM', 'meta_description' => 'Small series parts, stampings, assemblies and large steel parts – zinc plated on racks or in barrels at Elgarom.',
            'sections' => [
                ['industries', ['heading' => 'Industries served', 'intro' => '']],
                ['features', ['heading' => 'What kind of parts', 'intro' => 'The process is chosen according to the size and shape of the part.', 'items' => [
                    ['title' => 'Small parts in bulk', 'text' => 'Fasteners, stampings and other small series components are plated in barrels – up to 100 kg per load.'],
                    ['title' => 'Large parts or complex geometry', 'text' => 'Assemblies, machined parts and parts with visible surfaces are plated on racks – up to 1900 × 1200 × 450 mm.'],
                ]]],
                $cta,
            ]],
        'proiecte' => ['slug' => 'projects', 'nav' => 'Projects', 'title' => 'Projects and gallery',
            'lead' => 'The Elgarom facilities and, with our customers’ consent, completed projects.',
            'meta_title' => 'Projects and photo gallery | ELGAROM', 'meta_description' => 'Photos of the Elgarom zinc plating line, laboratory and wastewater treatment plant in Târgu Mureș.',
            'sections' => [
                ['projects', ['heading' => 'Projects', 'intro' => '']],
                ['gallery', ['heading' => 'Photo gallery', 'intro' => 'The plating line, the laboratory and the wastewater treatment plant in Târgu Mureș.', 'images' => ['img:linia-zincare-bai', 'img:linia-zincare-pod-transportor', 'img:linia-zincare-dispozitive', 'img:linia-zincare-panorama', 'img:centrifuge-top-coating', 'img:laborator-chimic', 'img:laborator-analiza', 'img:statie-epurare', 'img:hala-receptie-elgarom']]],
                $cta,
            ]],
        'calitate' => ['slug' => 'quality', 'nav' => 'Quality', 'title' => 'Quality and documents',
            'lead' => 'Automated process, monitored parameters and laboratory control.',
            'meta_title' => 'Quality and documents | ELGAROM', 'meta_description' => 'Quality control at Elgarom: automated process, electronic monitoring of parameters, in-house chemical laboratory.',
            'sections' => [
                ['intro', ['heading' => 'Reproducible quality', 'body' => "Automated process sequences and electronic monitoring of the process parameters ensure a consistent surface quality from batch to batch.\n\nIn our own laboratory we check the concentration of the active baths and the thickness of the deposited zinc layer.", 'image' => 'img:laborator-analiza', 'image_side' => 'right']],
                ['documents', ['heading' => 'Documents', 'intro' => 'Certificates and technical documents available for download.', 'category' => '']],
                $cta,
            ]],
        'intrebari-frecvente' => ['slug' => 'faq', 'nav' => 'FAQ', 'title' => 'Frequently asked questions',
            'lead' => 'Short answers about zinc plating, dimensions, materials and quotes.',
            'meta_title' => 'Zinc plating FAQ | ELGAROM', 'meta_description' => 'What type of zinc plating Elgarom performs, maximum dimensions, materials, post-treatments, quality and how to request a quote.',
            'sections' => [['faq', ['heading' => '', 'category' => '']], $cta]],
        'solicita-oferta' => ['slug' => 'request-a-quote', 'nav' => 'Quote', 'title' => 'Request a quote',
            'lead' => 'Tell us about your part. Fields you don’t know can be left empty.',
            'meta_title' => 'Request a zinc plating quote | ELGAROM', 'meta_description' => 'Quote request for electrolytic zinc plating: describe the part and quantity and upload drawings or photos.',
            'sections' => []],
        'contact' => ['slug' => 'contact', 'nav' => 'Contact', 'title' => 'Contact',
            'lead' => 'Sales, technical support and the address of the plating line in Târgu Mureș.',
            'meta_title' => 'Contact | ELGAROM Târgu Mureș', 'meta_description' => 'ELGAROM, Str. Barajului no. 3, Târgu Mureș, Romania. Sales +40 752 144 291, technical +40 748 118 339, vanzari@elgarom.ro.',
            'sections' => [
                ['contact', ['heading' => 'Contact details', 'intro' => '']],
                ['quote_short', ['heading' => 'Send a message', 'intro' => 'We reply to messages on working days.']],
            ]],
        'politica-de-confidentialitate' => ['slug' => 'privacy-policy', 'nav' => null, 'title' => 'Privacy policy', 'lead' => null,
            'meta_title' => 'Privacy policy | ELGAROM', 'meta_description' => 'How ELGAROM processes the personal data submitted through the website.',
            'sections' => [['text', ['heading' => '', 'body' => <<<'MD'
This policy explains which personal data we process when you use the elgarom.ro website, why, and what rights you have. The data controller is {{company}}, with its registered office at {{address}}. You can contact us at {{email}}.

## What data we process

- **Quote requests and messages:** name, company, email, phone, town, description of the parts and uploaded files (drawings, photos).
- **AI assistant:** the messages written in the conversation, kept for the period shown in the assistant window.
- **Technical data:** IP address (kept in pseudonymised form to protect against abuse), browser type.
- **Statistics and marketing:** only if you give your consent in the cookie banner.

## Why we process it

- to answer your request and prepare a quote (pre-contractual steps, Art. 6(1)(b) GDPR);
- for website security and abuse prevention (legitimate interest, Art. 6(1)(f) GDPR);
- for commercial information, only if you have separately ticked the marketing consent (consent, Art. 6(1)(a) GDPR).

## How long we keep it

Quote requests are kept as long as needed for quoting and the business relationship, then according to legal obligations. Conversations with the AI assistant are deleted automatically after the configured period. You can withdraw your marketing consent at any time.

## Who we share it with

The data is accessible only to authorised Elgarom staff. We use service providers for hosting and email. If you use the AI assistant, the messages are processed by the AI service provider. Statistical data is sent to Google only with your consent.

## Your rights

You have the right of access, rectification, erasure, restriction, portability and objection, and the right to withdraw consent. To exercise them, write to us at {{email}}. You can lodge a complaint with the Romanian National Supervisory Authority for Personal Data Processing (ANSPDCP, www.dataprotection.ro).
MD]]]],
        'politica-cookies' => ['slug' => 'cookie-policy', 'nav' => null, 'title' => 'Cookie policy', 'lead' => null,
            'meta_title' => 'Cookie policy | ELGAROM', 'meta_description' => 'Which cookies the ELGAROM website uses and how to change your preferences.',
            'sections' => [['text', ['heading' => '', 'body' => <<<'MD'
The website uses strictly necessary cookies and, only with your consent, statistics and marketing cookies.

## Categories

- **Strictly necessary:** session, form protection (CSRF) and remembering your cookie choice. They cannot be disabled.
- **Statistics (Google Analytics):** shows us how the website is used. Enabled only after consent.
- **Marketing (Google Ads):** measures the effectiveness of campaigns. Enabled only after consent.

Until you make a choice, no Google statistics or marketing tags are loaded. If you withdraw your consent, the Google cookies set by the website are deleted.

The Google map on the Contact page is loaded only when you click “Show map”.

## Changing your preferences

Use the “Cookie preferences” link in the footer. You can delete cookies at any time in your browser settings.
MD]]]],
    ],
    'services' => [
        'zincare-pe-dispozitive' => ['slug' => 'rack-zinc-plating', 'title' => 'Rack zinc plating',
            'summary' => 'For larger parts and assemblies that are hung individually on racks. Maximum envelope: 1900 × 1200 × 450 mm.',
            'body' => "The parts are mounted on racks and pass automatically through the baths of the line. Each part is exposed to the process in a controlled way – suitable for parts with complex geometry, visible surfaces or large dimensions.\n\n- Maximum envelope on a rack: **1900 × 1200 × 450 mm**\n- Weak-acid electrolytic zinc plating with brighteners\n- Passivation for higher corrosion resistance\n\nSend us the drawing or a photo of the part and we will tell you how it can be fixed on a rack."],
        'zincare-in-tambur' => ['slug' => 'barrel-zinc-plating', 'title' => 'Barrel zinc plating',
            'summary' => 'For small parts in bulk – fasteners, stampings, series components. Up to 100 kg of parts per load.',
            'body' => "Small parts are plated in bulk, in rotating barrels that pass automatically through the baths of the line. The rotation ensures that all faces of the parts in the load are coated.\n\n- Maximum load: **100 kg** of parts per barrel\n- Suitable for large series of small parts\n- Optional: top coat (sealing) on the centrifuge line\n\nFor a quote, tell us the part type, material and estimated quantity."],
        'pasivare-si-top-coating' => ['slug' => 'passivation-and-top-coat', 'title' => 'Passivation and top coat',
            'summary' => 'Treatments applied after plating for better corrosion resistance. Small parts are sealed on a dedicated centrifuge line.',
            'body' => "After the zinc layer is deposited, the parts are passivated. For small plated and passivated parts, Elgarom applies an additional surface sealing treatment (top coat).\n\nSealing is done in four steps: immersion of the parts in the solution, draining, centrifuging and drying (curing) of the applied layer.\n\nThe type of passivation and the need for a top coat are decided together with the technical team, according to the requirements of the part."],
        'consultanta-tehnica-si-laborator' => ['slug' => 'technical-advice-and-laboratory', 'title' => 'Technical advice and laboratory control',
            'summary' => 'We review the part requirements before quoting and check the active baths and the deposited layer thickness in the laboratory.',
            'body' => "Every part has its own requirements. The technical team assesses the material, geometry and service conditions and proposes the right process, in dialogue with you.\n\nThe Elgarom chemical laboratory performs:\n\n- analysis of the concentration of the components in the active baths;\n- measurement of the deposited zinc layer thickness;\n- chemical and spectrophotometric analysis of the treated water.\n\nFor technical questions: **+40 748 118 339**."],
    ],
    'faqs' => [
        ['proces', 'What type of zinc plating do you perform?', 'Weak-acid electrolytic zinc plating, on racks (hung parts) and in barrels (small parts in bulk). Electrolytic zinc plating is a different process from hot-dip galvanizing (immersion in molten zinc).'],
        ['capacitati', 'What are the maximum part dimensions?', 'On racks we can plate parts up to 1900 × 1200 × 450 mm. In barrels, the maximum load is 100 kg of small parts. For parts close to these limits, please send us the drawing for review.'],
        ['materiale', 'Which materials can you plate?', 'We have processes for low-, medium- and high-carbon steel, stainless steels, and alloy and high-alloy steels. We confirm the right process after reviewing the part.'],
        ['proces', 'Which treatments do you apply after plating?', 'Parts are passivated after plating. For small parts we can also apply a top coat (sealing) on the centrifuge line, for better corrosion resistance.'],
        ['calitate', 'How do you check the layer quality?', 'Process parameters are monitored electronically on the line. In our own laboratory we analyse the active baths and measure the thickness of the deposited zinc layer.'],
        ['oferta', 'What information do you need for a quote?', 'It helps to know the part type, material, quantity, dimensions and to have a drawing or a photo. If you do not know all of it, send what you have – the team will contact you for details.'],
        ['oferta', 'Can I send technical drawings?', 'Yes. In the quote form you can upload up to 6 files: PDF, images (JPG, PNG, WebP) or CAD files (DXF, DWG, STEP, IGES), up to 15 MB each. The files are accessible only to the Elgarom team.'],
        ['oferta', 'How much does zinc plating cost and how long does it take?', 'Price and lead time depend on the part, quantity and requirements, so we cannot estimate them without a review. Send a quote request and the team will reply with a concrete proposal.'],
        ['durabilitate', 'How many years do zinc plated parts last?', 'The protection period depends on the layer thickness, on passivation and top coat and, above all, on the environment in which the part is used. We do not give a general value in years: we discuss the concrete requirements of your application.'],
        ['mediu', 'How do you treat the process wastewater?', 'Acid and alkaline wastewater is treated in an automatic treatment and neutralisation plant, so that the discharged water meets current environmental regulations.'],
    ],
    'capabilities' => [
        'process' => ['label' => 'Process', 'value' => 'Weak-acid electrolytic zinc plating'],
        'execution' => ['label' => 'Execution', 'value' => 'On racks and in barrels'],
        'rack_envelope' => ['label' => 'Maximum part envelope on a rack'],
        'barrel_load' => ['label' => 'Maximum barrel load'],
        'automation' => ['label' => 'Automation', 'value' => 'Fully automated line, parameters monitored electronically'],
        'post' => ['label' => 'Post-treatments', 'value' => 'Passivation; top coat (sealing) for small parts'],
        'lab' => ['label' => 'Laboratory control', 'value' => 'Analysis of the active baths, zinc layer thickness measurement'],
        'water' => ['label' => 'Wastewater', 'value' => 'Automatic treatment and neutralisation plant'],
        'thickness' => ['label' => 'Available layer thicknesses'],
        'passivation_types' => ['label' => 'Passivation types'],
        'salt_spray' => ['label' => 'Salt spray resistance (with test method)'],
        'rack_weight' => ['label' => 'Maximum weight per rack'],
        'barrel_part_size' => ['label' => 'Maximum part size in a barrel'],
        'tanks' => ['label' => 'Bath dimensions'],
        'throughput' => ['label' => 'Production capacity'],
    ],
    'industries' => ['Machine and equipment building', 'Automotive – components', 'Construction and fasteners', 'Electrical equipment', 'Furniture and hardware'],
    'media' => [
        'linia-zincare-bai' => 'Elgarom electrolytic zinc plating line – process tanks and service walkway',
        'linia-zincare-pod-transportor' => 'Automatic transport crane above the zinc plating line tanks',
        'linia-zincare-dispozitive' => 'Top view of the zinc plating line with the transport racks',
        'linia-zincare-panorama' => 'Panorama of the electrolytic zinc plating line tanks',
        'statie-epurare' => 'Treatment and neutralisation plant for waste water',
        'centrifuge-top-coating' => 'Centrifuge equipment for top coat on small parts',
        'laborator-chimic' => 'Elgarom chemical laboratory',
        'laborator-analiza' => 'Chemical analysis in the Elgarom laboratory',
        'hala-receptie-elgarom' => 'Elgarom reception, with a view of the production hall',
        'receptie-elgarom' => 'Entrance to the Elgarom hall',
        'sala-sedinte' => 'Elgarom meeting room',
        'birouri-coridor' => 'Elgarom office area',
    ],
];
