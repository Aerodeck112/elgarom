<?php

// Frezare plăci de ștanță – ore. Sursa: «Calculator_Ore_Frezare_GENOS_M460_VE_e_v5_3.xlsx».
// Ieșirile se citesc din foaia «Interfata_API», contractul stabil definit chiar în workbook.
return [
    'key' => 'frezare-genos-m460',
    'title' => 'Frezare — OKUMA GENOS M460V-e',
    'subtitle' => 'Ore ciclu și ore de ocupare a mașinii, pe lot',
    'process' => 'Frezare',
    'entity' => 'fepo',
    'kind' => 'hours',
    'machine' => ['code' => 'GENOS-M460', 'name' => 'OKUMA GENOS M460V-e (centru de frezare)'],
    'file_hint' => 'Calculator_Ore_Frezare_GENOS_M460_VE_e_v5_3.xlsx',
    'requires' => ['Interfata_API!A39' => 'STATUS_CODE', 'Interfata_API!A42' => 'HOURS_OCC_LOT'],
    'sheets' => [
        'Calculator' => ['role' => 'input', 'label' => 'Calculator'],
        'Operatii_detaliate' => ['role' => 'input', 'label' => 'Operații detaliate'],
        'Rezumat' => ['role' => 'reference', 'label' => 'Rezumat'],
        'Parametri' => ['role' => 'params', 'label' => 'Parametri'],
        'Regimuri' => ['role' => 'params', 'label' => 'Regimuri'],
        'Calibrare_85' => ['role' => 'params', 'label' => 'Calibrare'],
        'Interfata_API' => ['role' => 'reference', 'label' => 'Interfață API'],
        'Manual' => ['role' => 'doc', 'label' => 'Manual'],
        'Surse_si_ipoteze' => ['role' => 'doc', 'label' => 'Surse și ipoteze'],
        'Jurnal_modificari' => ['role' => 'doc', 'label' => 'Jurnal modificări'],
    ],
    'variants' => [
        'lot' => [
            'label' => 'Ocupare mașină – lot',
            'status' => 'Interfata_API!F39',
            'ok' => '/^OK_/',
            'warn' => '/^WARN$/',
            'status_text' => 'Interfata_API!F40',
            'hours' => 'Interfata_API!F42',
            'warnings' => ['Interfata_API!E55', 'Interfata_API!E56', 'Interfata_API!E57', 'Interfata_API!E58', 'Interfata_API!E59', 'Interfata_API!E60', 'Interfata_API!E61',
                'Interfata_API!E62', 'Interfata_API!E63', 'Interfata_API!E64', 'Interfata_API!E65', 'Interfata_API!E66', 'Interfata_API!E67', 'Interfata_API!E68'],
            'outputs' => [
                'Ore ciclu – lot' => 'Interfata_API!F41',
                'Ore ocupare lot – minim' => 'Interfata_API!F43',
                'Ore ocupare lot – maxim' => 'Interfata_API!F44',
                'Ore ocupare medie pe placă' => 'Interfata_API!F45',
                'Bandă de incertitudine (±)' => 'Interfata_API!F46',
                'Lucrări de calibrare pentru metodă' => 'Interfata_API!F47',
                'Ciclu prima piesă [min]' => 'Interfata_API!F48',
                'Ciclu piesă următoare [min]' => 'Interfata_API!F49',
                'Încărcare + măsurători pe piesă [min]' => 'Interfata_API!F50',
                'Reglaj + scule noi + încălzire pe lot [min]' => 'Interfata_API!F51',
                'Scule distincte' => 'Interfata_API!F52',
                'Schimbări de sculă pe piesă' => 'Interfata_API!F53',
                'Masă semifabricat [kg]' => 'Interfata_API!F54',
            ],
        ],
    ],
];
