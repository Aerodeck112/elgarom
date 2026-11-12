<?php

// Strunjire – ore. Sursa: «Calculator_Ore_Strunjire_GENOS_L3000e_MY_v1_3_1.xlsx».
return [
    'key' => 'strunjire-genos-l3000',
    'title' => 'Strunjire — OKUMA GENOS L3000-e MY',
    'subtitle' => 'Ore de funcționare: piesă unicat și serie / lot',
    'process' => 'Strunjire',
    'entity' => 'fepo',
    'kind' => 'hours',
    'machine' => ['code' => 'GENOS-L3000', 'name' => 'OKUMA GENOS L3000-e MY (strung CNC)'],
    'file_hint' => 'Calculator_Ore_Strunjire_GENOS_L3000e_MY_v1_3_1.xlsx',
    'requires' => ['Calculator!G20' => 'STATUS GENERAL', 'Calculator!G31' => 'ORE OCUPARE LOT'],
    'sheets' => [
        'Calculator' => ['role' => 'input', 'label' => 'Calculator'],
        'Operatii_detaliate' => ['role' => 'input', 'label' => 'Operații detaliate'],
        'Rezumat' => ['role' => 'reference', 'label' => 'Rezumat'],
        'Parametri' => ['role' => 'params', 'label' => 'Parametri'],
        'Regimuri' => ['role' => 'params', 'label' => 'Regimuri'],
        'Calibrare_85' => ['role' => 'params', 'label' => 'Calibrare'],
        'Control_integritate' => ['role' => 'reference', 'label' => 'Control integritate'],
        'Manual' => ['role' => 'doc', 'label' => 'Manual'],
        'Surse_si_ipoteze' => ['role' => 'doc', 'label' => 'Surse și ipoteze'],
        'Audit_v1_3' => ['role' => 'doc', 'label' => 'Audit v1.3'],
        'Jurnal_modificari' => ['role' => 'doc', 'label' => 'Jurnal modificări'],
    ],
    'variants' => [
        'lot' => [
            'label' => 'Ocupare mașină – lot',
            'status' => 'Calculator!H20',
            'ok' => '/^ORE CALCULATE/u',
            'block' => '/^BLOCAJ/u',
            'hours' => 'Calculator!H31',
            'warnings' => ['Calculator!H5', 'Calculator!H6', 'Calculator!H7', 'Calculator!H8', 'Calculator!H9', 'Calculator!H10', 'Calculator!H11', 'Calculator!H12',
                'Calculator!H13', 'Calculator!H14', 'Calculator!H15', 'Calculator!H16', 'Calculator!H17', 'Calculator!H18', 'Calculator!H21', 'Calculator!H25'],
            'outputs' => [
                'Ore ciclu (unicat)' => 'Calculator!H23',
                'Ore ocupare (unicat)' => 'Calculator!H24',
                'Ciclu piesă serie [min]' => 'Calculator!H28',
                'Ocupare piesă serie [min]' => 'Calculator!H29',
                'Ocupare lot [min]' => 'Calculator!H30',
                'Ore / piesă (medie lot)' => 'Calculator!H32',
                'Ore ciclu / piesă serie' => 'Calculator!H33',
                'Calibrare (metoda aleasă)' => 'Calculator!H19',
            ],
        ],
    ],
];
