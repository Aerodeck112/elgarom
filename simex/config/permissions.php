<?php

/*
 * Permissions and roles. Every route and every sensitive view checks a permission on the server.
 * "*" grants everything. To change what a role may do, edit this file and ship it as an update.
 */
return [
    'labels' => [
        'dashboard' => 'Panou principal',
        'crm.view' => 'Clienți și contacte – vizualizare',
        'crm.manage' => 'Clienți și contacte – adăugare și modificare',
        'crm.delete' => 'Clienți și contacte – ștergere, export și cereri GDPR',
        'rfq.view' => 'Cereri de ofertă – vizualizare',
        'rfq.manage' => 'Cereri de ofertă – gestionare',
        'calc.use' => 'Calculatoare – utilizare',
        'costs.view' => 'Vizualizarea costurilor interne',
        'margins.view' => 'Vizualizarea marjelor',
        'rates.view' => 'Tarife și parametri de calcul – vizualizare',
        'rates.edit' => 'Modificarea tarifelor și parametrilor',
        'formulas.manage' => 'Modificarea formulelor (import și activare modele Excel)',
        'quotes.view' => 'Oferte – vizualizare',
        'quotes.manage' => 'Oferte – creare și modificare',
        'quotes.discount' => 'Aplicarea discounturilor',
        'quotes.approve' => 'Aprobarea ofertelor',
        'quotes.send' => 'Trimiterea emailurilor către clienți',
        'export' => 'Export (PDF, Excel, CSV)',
        'library.view' => 'Materiale, utilaje, operații, furnizori – vizualizare',
        'library.manage' => 'Materiale, utilaje, operații, furnizori – modificare',
        'marketing' => 'Email marketing',
        'prospecting' => 'AI Prospectare Europa',
        'reports' => 'Rapoarte și analiză comercială',
        'site' => 'Website public și companiile grupului',
        'templates' => 'Șabloane și documente',
        'users' => 'Administrarea utilizatorilor',
        'settings' => 'Configurări generale',
        'smtp' => 'Configurare SMTP',
        'api.keys' => 'Configurarea cheilor API',
        'security' => 'Politici de securitate',
        'audit' => 'Audit și activitate',
        'backups' => 'Backup și restaurare',
        'updates' => 'Actualizare aplicație (ZIP)',
    ],

    'roles' => [
        'super_admin' => ['label' => 'Super Administrator', 'permissions' => ['*']],
        'admin' => ['label' => 'Administrator', 'permissions' => [
            'dashboard', 'crm.view', 'crm.manage', 'crm.delete', 'rfq.view', 'rfq.manage', 'calc.use', 'costs.view', 'margins.view', 'rates.view', 'rates.edit',
            'formulas.manage', 'quotes.view', 'quotes.manage', 'quotes.discount', 'quotes.approve', 'quotes.send', 'export', 'library.view', 'library.manage',
            'marketing', 'prospecting', 'reports', 'site', 'templates', 'users', 'settings', 'smtp', 'api.keys', 'security', 'audit', 'backups',
        ]],
        'director_comercial' => ['label' => 'Director Comercial', 'permissions' => [
            'dashboard', 'crm.view', 'crm.manage', 'crm.delete', 'rfq.view', 'rfq.manage', 'calc.use', 'costs.view', 'margins.view', 'rates.view',
            'quotes.view', 'quotes.manage', 'quotes.discount', 'quotes.approve', 'quotes.send', 'export', 'library.view', 'marketing', 'prospecting',
            'reports', 'templates', 'audit',
        ]],
        'inginer_ofertare' => ['label' => 'Inginer Ofertare', 'permissions' => [
            'dashboard', 'crm.view', 'rfq.view', 'rfq.manage', 'calc.use', 'costs.view', 'rates.view', 'quotes.view', 'quotes.manage', 'export',
            'library.view', 'library.manage',
        ]],
        'agent_vanzari' => ['label' => 'Agent Vânzări', 'permissions' => [
            'dashboard', 'crm.view', 'crm.manage', 'rfq.view', 'rfq.manage', 'quotes.view', 'quotes.manage', 'quotes.send', 'export', 'library.view',
        ]],
        'marketing' => ['label' => 'Marketing', 'permissions' => ['dashboard', 'crm.view', 'marketing', 'prospecting', 'templates']],
        'viewer' => ['label' => 'Vizualizare', 'permissions' => ['dashboard', 'crm.view', 'rfq.view', 'quotes.view', 'library.view']],
    ],

    // Roles that can never be left without two-factor authentication (in this application: all of them).
    'privileged' => ['super_admin', 'admin', 'director_comercial'],
];
