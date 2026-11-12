<?php

// Rectificare plană – ore manoperă. Sursa: «Calculator_OKAMOTO_ore_manopera.xlsx».
return [
    'key' => 'rectificare-okamoto',
    'title' => 'Rectificare plană — OKAMOTO ACC106CA-iQ',
    'subtitle' => 'Ore de manoperă pentru o piesă',
    'process' => 'Rectificare',
    'entity' => 'fepo',
    'kind' => 'hours',
    'machine' => ['code' => 'OKAMOTO-ACC106CA', 'name' => 'OKAMOTO ACC106CA-iQ (rectificat plan)'],
    'file_hint' => 'Calculator_OKAMOTO_ore_manopera.xlsx',
    'requires' => ['Calculator!A1' => 'OKAMOTO', 'Calculator!A46' => 'ORE MANOPERĂ'],
    'sheets' => [
        'Calculator' => ['role' => 'input', 'label' => 'Calculator'],
        'Suprafete' => ['role' => 'input', 'label' => 'Suprafețe (personalizat)'],
        'Motor' => ['role' => 'reference', 'label' => 'Motor de calcul'],
        'Parametri' => ['role' => 'params', 'label' => 'Parametri'],
        'Materiale' => ['role' => 'params', 'label' => 'Materiale'],
        'Validare' => ['role' => 'params', 'label' => 'Validare pe teren'],
        'Manual' => ['role' => 'doc', 'label' => 'Manual'],
    ],
    'variants' => [
        'standard' => [
            'label' => 'Normă standard',
            'status' => 'Calculator!B32',
            'ok' => '/^OK/u',
            'hours' => 'Calculator!B46',
            'warnings' => ['Calculator!B33'],
            'outputs' => [
                'Timp degroșare [min]' => 'Calculator!B36',
                'Timp finisare [min]' => 'Calculator!B37',
                'Timp spark-out [min]' => 'Calculator!B38',
                'Timp diamantare [min]' => 'Calculator!B39',
                'Timp automat corectat [min]' => 'Calculator!B40',
                'Timpi auxiliari pe mașină [min]' => 'Calculator!B41',
                'Rezervă tehnologică [min]' => 'Calculator!B42',
                'Timp pe mașină (cu operator) [min]' => 'Calculator!B43',
                'Programare în afara mașinii [min]' => 'Calculator!B44',
                'Total manoperă [min]' => 'Calculator!B45',
                'Grupă material' => 'Calculator!B34',
                'Cerințe strânse / clasă' => 'Calculator!B35',
            ],
        ],
    ],
];
