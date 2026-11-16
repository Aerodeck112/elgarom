<?php

return [
    // Application version. Bumped by every signed update package.
    'version' => '0.1.6',

    // Calculation engine version, stored with every saved calculation.
    'engine_version' => '1.0.0',

    'updates' => [
        // Trusted Ed25519 public keys (base64). Update packages must be signed by one of these.
        // The private key is never stored on the server nor inside packages.
        'public_keys' => [
            'simex-release-2026' => '0Jskk5KT0uwzKwR0IP1EOHq0EUrTu1wPDPRDd8HuKes=',
        ],
        'max_package_bytes' => 150 * 1024 * 1024,
        'max_entries' => 40000,
        'max_uncompressed_bytes' => 500 * 1024 * 1024,
        // Paths an update may never overwrite (user data, secrets, local config).
        'protected_paths' => [
            '.env', 'storage/', 'public/media/', 'bootstrap/cache/', 'install.lock',
        ],
    ],

    'uploads' => [
        'document_max_kb' => 30720,
        // Technical documents attached to companies, RFQs and quotes. Stored privately, never executable.
        'document_extensions' => ['pdf', 'xlsx', 'xls', 'docx', 'doc', 'csv', 'txt', 'jpg', 'jpeg', 'png', 'webp', 'dxf', 'dwg', 'step', 'stp', 'igs', 'iges', 'zip'],
        'logo_max_kb' => 2048,
        'logo_extensions' => ['png', 'jpg', 'jpeg', 'webp', 'svg'],
        'workbook_max_kb' => 20480,
    ],

    'languages' => ['ro' => 'Română', 'en' => 'English', 'de' => 'Deutsch'],

    'currencies' => ['EUR', 'RON', 'USD', 'GBP', 'CHF', 'HUF'],

    // «Noutăți» on the dashboard: shown once per user until «Am văzut».
    'news' => [
        ['version' => '0.1.6', 'date' => '16.11.2026', 'items' => [
            'Interfața după aplicația ARGENTO: antet cu firma, numărul următoarei oferte, cursul EUR și tab-uri (Dashboard, Ofertă piesă, Tarife, Ofertă ștanță, Tutorial, Oferte, Integrare AI).',
            'Calculatorul pe secțiuni numerotate, cu banda «Rezultat final» mereu vizibilă, avertizări pe fiecare secțiune și Registru de avertizări.',
            'Drafturi salvate și «Resetează la exemplul inițial» direct din calculator; oferta se creează dintr-un clic din «Audit final».',
            'Cursul EUR se preia automat de la BNR; registrul de oferte are indicatori și acțiuni (ștergerea doar pentru administrator).',
            'Integrare AI: email de însoțire, rezumat de cerere și traducere, rulate pe server, fără a modifica cifre.',
        ]],
    ],

    'ai' => [
        // Models offered in the settings screen. Token prices are entered by the administrator, not assumed here.
        'models' => [
            'claude-opus-5-5' => 'Claude Opus 5.5',
            'claude-sonnet-5-5' => 'Claude Sonnet 5.5',
            'claude-haiku-5-5' => 'Claude Haiku 5.5',
        ],
        'timeout' => 45,
    ],

    'mail' => [
        'max_attempts' => 8,
    ],

    // Production limits confirmed on the Elgarom website (rack line and barrels). Used only for warnings.
    'zinc_limits' => [
        'rack' => ['length_mm' => 1900, 'width_mm' => 1200, 'depth_mm' => 450],
        'barrel' => ['load_kg' => 100],
    ],
];
