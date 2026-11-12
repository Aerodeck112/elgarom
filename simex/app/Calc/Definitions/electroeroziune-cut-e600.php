<?php

// Electroeroziune cu fir – timp de debitare. Sursa: «Calculator_CUT_E_600_v18.xlsx».
// Celulele de intrare sunt marcate în workbook prin umplerea galbenă (nu prin protecție).
return [
    'key' => 'electroeroziune-cut-e600',
    'title' => 'Electroeroziune cu fir — AgieCharmilles CUT E 600',
    'subtitle' => 'Timp de debitare: estimare rapidă sau detaliată, contur cu contur',
    'process' => 'Electroeroziune cu fir',
    'entity' => 'fepo',
    'kind' => 'hours',
    'machine' => ['code' => 'CUT-E-600', 'name' => 'AgieCharmilles CUT E 600 (electroeroziune cu fir)'],
    'file_hint' => 'Calculator_CUT_E_600_v18.xlsx',
    'input_fill' => 'FFFFF2CC',
    'requires' => ['Estimare_rapida!A23' => 'TIMP TOTAL LOT', 'Estimare_detaliata!A50' => 'TIMP TOTAL LOT'],
    'sheets' => [
        'Estimare_rapida' => ['role' => 'input', 'label' => 'Estimare rapidă'],
        'Estimare_detaliata' => ['role' => 'input', 'label' => 'Estimare detaliată'],
        'Motor_piese_matrita' => ['role' => 'input', 'label' => 'Piese matriță (listă)'],
        'Calibrare_produs' => ['role' => 'params', 'label' => 'Calibrare produs'],
        'Parametri' => ['role' => 'params', 'label' => 'Parametri'],
        'Matrice_viteze_025' => ['role' => 'params', 'label' => 'Matrice viteze Ø0,25'],
        'Istoric_viteze_CUT_E600' => ['role' => 'params', 'label' => 'Istoric viteze'],
        'Validare_10pct' => ['role' => 'params', 'label' => 'Validare ±10%'],
        'HELP' => ['role' => 'doc', 'label' => 'Ajutor'],
        'Validare_surse' => ['role' => 'doc', 'label' => 'Validare surse'],
        'Jurnal_modificari' => ['role' => 'doc', 'label' => 'Jurnal modificări'],
    ],
    'variants' => [
        'rapid' => [
            'label' => 'Estimare rapidă',
            'status' => 'Estimare_rapida!B27',
            'ok' => '/^OK/u',
            'warn' => '/^(ATENȚIE|OK – VERIFIC)/u',
            'hours' => 'Estimare_rapida!B23',
            'outputs' => [
                'Timp / piesă [min]' => 'Estimare_rapida!B20',
                'Prinderi lot [min]' => 'Estimare_rapida!B21',
                'Timp total lot [min]' => 'Estimare_rapida!B22',
                'Interval de încredere [ore]' => 'Estimare_rapida!B24',
                'Acuratețe demonstrată' => 'Estimare_rapida!B25',
                'Grad calibrare viteze matrice' => 'Estimare_rapida!B26',
                'Factor corecție produs' => 'Estimare_rapida!B19',
            ],
        ],
        'detaliat' => [
            'label' => 'Estimare detaliată',
            'status' => 'Estimare_detaliata!B54',
            'ok' => '/^OK/u',
            'warn' => '/^(ATENȚIE|OK – VERIFIC)/u',
            'hours' => 'Estimare_detaliata!B50',
            'outputs' => [
                'Timp / piesă [min]' => 'Estimare_detaliata!B47',
                'Prinderi + pregătire lot [min]' => 'Estimare_detaliata!B48',
                'Timp total lot [min]' => 'Estimare_detaliata!B49',
                'Interval de încredere [ore]' => 'Estimare_detaliata!B51',
                'Acuratețe demonstrată' => 'Estimare_detaliata!B52',
                'Grad calibrare (ponderat pe timp)' => 'Estimare_detaliata!B53',
                'Clasă complexitate aplicată' => 'Estimare_detaliata!B24',
                'Timp mediu / piesă inclusiv prinderi [min]' => 'Estimare_detaliata!B55',
            ],
        ],
    ],
];
