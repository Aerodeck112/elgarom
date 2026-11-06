<?php

return [
    // Application version. Bumped by every signed update package.
    'version' => '1.0.0',

    'default_locale' => 'ro',

    // Locales with published content. Add a locale here only when its translations are complete.
    'locales' => [
        'ro' => ['name' => 'Română', 'published' => true],
        'en' => ['name' => 'English', 'published' => false],
        'hu' => ['name' => 'Magyar', 'published' => false],
    ],

    'updates' => [
        // Trusted Ed25519 public keys (base64). Update packages must be signed by one of these.
        // The private key is never stored on the server nor inside packages.
        'public_keys' => [
            'elgarom-release-2026' => '6iE8R6rWqSv2kO9jXc2FnB6JH1gc4U0YcfJacQVYsmU=',
        ],
        'max_package_bytes' => 120 * 1024 * 1024,
        'max_entries' => 30000,
        'max_uncompressed_bytes' => 400 * 1024 * 1024,
        // Paths an update may never overwrite (user data, secrets, local config).
        'protected_paths' => [
            '.env', 'storage/', 'public/media/', 'bootstrap/cache/', 'install.lock',
        ],
    ],

    'uploads' => [
        'quote_max_files' => 6,
        'quote_max_kb' => 15360,
        'quote_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'dxf', 'dwg', 'step', 'stp', 'igs', 'iges'],
        'media_max_kb' => 20480,
        'document_max_kb' => 20480,
    ],

    'mail' => [
        'max_attempts' => 8,
    ],
];
