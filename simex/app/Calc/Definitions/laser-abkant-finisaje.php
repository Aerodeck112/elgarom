<?php

// Cost și preț piese din tablă: laser, abkant, sudură, finisaje, ambalare.
// Sursa: «Calculator_Cost_Laser_Abkant_Finisaje_v321_MODEL.xlsx».
return [
    'key' => 'laser-abkant-finisaje',
    'title' => 'Piese din tablă — laser, abkant, sudură, finisaje, ambalare',
    'subtitle' => 'Cost complet și preț pe reper, până la 30 de repere pe ofertă',
    'process' => 'Debitare laser, îndoire, sudură, finisaje',
    'entity' => 'argento',
    'kind' => 'cost',
    'machine' => null,
    'file_hint' => 'Calculator_Cost_Laser_Abkant_Finisaje_v321_MODEL.xlsx',
    'requires' => ['14_VERIFICARI!A4' => 'STATUS GENERAL', '13_OFERTA!A44' => 'TOTAL OFERTĂ'],
    'sheets' => [
        '01_DATE_PROIECT' => ['role' => 'input', 'label' => '1 · Date proiect'],
        '06_REPERE' => ['role' => 'input', 'label' => '6 · Repere și foi'],
        '07_CAM' => ['role' => 'input', 'label' => '7 · CAM'],
        '08_LASER' => ['role' => 'input', 'label' => '8 · Laser'],
        '09_ABKANT' => ['role' => 'input', 'label' => '9 · Abkant'],
        '09A_INDOIRI' => ['role' => 'input', 'label' => '9A · Îndoiri'],
        '09B_SUDURA' => ['role' => 'input', 'label' => '9B · Sudură'],
        '10_FINISAJE' => ['role' => 'input', 'label' => '10 · Finisaje'],
        '11_AMBALARE' => ['role' => 'input', 'label' => '11 · Ambalare'],
        '12_CALCUL_GENERAL' => ['role' => 'reference', 'label' => '12 · Calcul general', 'internal' => true],
        '14_VERIFICARI' => ['role' => 'reference', 'label' => '14 · Verificări'],
        '13_OFERTA' => ['role' => 'reference', 'label' => '13 · Ofertă'],
        '16_SCENARII_CANTITATE' => ['role' => 'input', 'label' => '16 · Scenarii cantitate'],
        '02_CONFIGURARE' => ['role' => 'params', 'label' => '2 · Configurare'],
        '03_TARIFE_UT' => ['role' => 'params', 'label' => '3 · Tarife utilaje'],
        '04_MATERIALE' => ['role' => 'params', 'label' => '4 · Materiale'],
        '05_VITEZE_LASER' => ['role' => 'params', 'label' => '5 · Viteze laser'],
        'SUDURA_CONFIG' => ['role' => 'params', 'label' => 'Configurare sudură'],
        'ABKANT_CONFIG' => ['role' => 'params', 'label' => 'Configurare abkant'],
        'START' => ['role' => 'doc', 'label' => 'Start'],
        'MANUAL_COMPLETARE' => ['role' => 'doc', 'label' => 'Manual de completare'],
        'INSTRUCTIUNI_COMPLETE' => ['role' => 'doc', 'label' => 'Instrucțiuni complete'],
        'GHID_FINISAJE' => ['role' => 'doc', 'label' => 'Ghid finisaje'],
        'GHID_SUDURA' => ['role' => 'doc', 'label' => 'Ghid sudură'],
        '15_SURSE' => ['role' => 'doc', 'label' => 'Surse'],
    ],
    'variants' => [
        'oferta' => [
            'label' => 'Ofertă generală',
            'status' => '14_VERIFICARI!B4',
            'ok' => '/^PASS$/',
            'status_text' => '13_OFERTA!E6',
            'total' => '13_OFERTA!I44',
            'currency' => 'EUR',
            'outputs' => [
                'Total ofertă EUR (fără TVA)' => '13_OFERTA!I44',
                'Total ofertă RON (fără TVA)' => '13_OFERTA!J44',
                'Subtotal repere EUR' => '13_OFERTA!I42',
                'Ajustare valoare minimă comandă EUR' => '13_OFERTA!I43',
                'Curs RON/EUR' => '13_OFERTA!E3',
                'Piese incluse' => '14_VERIFICARI!B6',
                'Avertizări (nu blochează)' => '14_VERIFICARI!B22',
            ],
        ],
    ],
    // Rândurile ofertei: fiecare devine o poziție în oferta comercială.
    'lines' => [
        'sheet' => '13_OFERTA', 'first' => 11, 'last' => 40,
        'cols' => ['code' => 'B', 'name' => 'C', 'qty' => 'D', 'cost' => 'E', 'markup' => 'F', 'unit_price' => 'G', 'total' => 'I', 'status' => 'K'],
        'currency' => 'EUR',
    ],
    // Coloanele cu costuri interne, ascunse utilizatorilor fără permisiunea «costuri interne».
    'checks' => ['sheet' => '14_VERIFICARI', 'first' => 5, 'last' => 22, 'label' => 'A', 'value' => 'B', 'expected' => 'C', 'where' => 'D'],
];
