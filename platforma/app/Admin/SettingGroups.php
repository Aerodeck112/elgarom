<?php

namespace App\Admin;

/** Settings forms. Keys map 1:1 to rows in the `settings` table. Type "secret" is stored encrypted. */
class SettingGroups
{
    public static function all(): array
    {
        return [
            'company' => ['label' => 'Companie, contact și subsol', 'perm' => 'content', 'fields' => [
                ['name' => 'company.name', 'label' => 'Nume afișat', 'type' => 'text', 'rules' => 'required|string|max:120'],
                ['name' => 'company.legal_name', 'label' => 'Denumire juridică', 'type' => 'text', 'hint' => 'Apare în subsol, politici și date structurate. De completat.'],
                ['name' => 'company.cui', 'label' => 'CUI', 'type' => 'text', 'rules' => 'nullable|string|max:20|regex:/^(RO)?\d{2,10}$/i'],
                ['name' => 'company.reg_com', 'label' => 'Nr. Registrul Comerțului', 'type' => 'text', 'rules' => 'nullable|string|max:40'],
                ['name' => 'company.tagline', 'label' => 'Descriere scurtă', 'type' => 'text', 'max' => 200],
                ['name' => 'company.street', 'label' => 'Stradă și număr', 'type' => 'text', 'rules' => 'required|string|max:160'],
                ['name' => 'company.city', 'label' => 'Localitate', 'type' => 'text', 'rules' => 'required|string|max:80'],
                ['name' => 'company.county', 'label' => 'Județ', 'type' => 'text', 'max' => 60],
                ['name' => 'company.postal_code', 'label' => 'Cod poștal', 'type' => 'text', 'rules' => 'nullable|string|max:10'],
                ['name' => 'company.hours', 'label' => 'Program', 'type' => 'text', 'max' => 160, 'hint' => 'Ex. Luni–Vineri 8:00–16:30. Gol = nu se afișează.'],
                ['name' => 'company.phones', 'label' => 'Telefoane', 'type' => 'repeater', 'fields' => [
                    ['name' => 'label', 'label' => 'Departament', 'type' => 'text', 'max' => 40],
                    ['name' => 'number', 'label' => 'Număr', 'type' => 'text', 'max' => 30],
                ]],
                ['name' => 'company.emails', 'label' => 'Adrese de email', 'type' => 'repeater', 'fields' => [
                    ['name' => 'label', 'label' => 'Departament', 'type' => 'text', 'max' => 40],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'text', 'max' => 190],
                ]],
                ['name' => 'company.map_query', 'label' => 'Adresă pentru hartă', 'type' => 'text', 'max' => 200],
                ['name' => 'company.logo_id', 'label' => 'Logo (pentru fundal deschis)', 'type' => 'media'],
                ['name' => 'company.logo_dark_id', 'label' => 'Logo (pentru fundal închis)', 'type' => 'media'],
                ['name' => 'group.name', 'label' => 'Grup', 'type' => 'text', 'max' => 120],
                ['name' => 'group.url', 'label' => 'Website grup', 'type' => 'url'],
                ['name' => 'footer.text', 'label' => 'Text subsol', 'type' => 'textarea', 'max' => 400],
            ]],
            'seo' => ['label' => 'SEO', 'perm' => 'seo', 'fields' => [
                ['name' => 'seo.site_name', 'label' => 'Numele site-ului', 'type' => 'text', 'rules' => 'required|string|max:60'],
                ['name' => 'seo.default_description', 'label' => 'Descriere implicită', 'type' => 'textarea', 'max' => 170],
                ['name' => 'seo.default_og_image_id', 'label' => 'Imagine implicită pentru distribuire', 'type' => 'media'],
            ]],
            'cookies' => ['label' => 'Cookies și consimțământ', 'perm' => 'google', 'fields' => [
                ['name' => 'cookies.consent_mode', 'label' => 'Mod de consimțământ', 'type' => 'select', 'options' => [
                    'basic' => 'De bază – nicio etichetă Google înainte de acord (recomandat)',
                    'advanced' => 'Avansat – etichetele se încarcă cu Consent Mode „denied” (pinguri fără cookie-uri)',
                ], 'rules' => 'required|in:basic,advanced'],
                ['name' => 'cookies.policy_version', 'label' => 'Versiunea politicii', 'type' => 'number', 'rules' => 'required|integer|min:1|max:999', 'hint' => 'Măriți numărul după o modificare a politicii: vizitatorii vor fi întrebați din nou.'],
            ]],
            'smtp' => ['label' => 'SMTP și notificări', 'perm' => 'smtp', 'fields' => [
                ['name' => 'smtp.host', 'label' => 'Server SMTP', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9.\-]+$/i'], 'hint' => 'Ex. mail.elgarom.ro (din cPanel → Conturi email → Connect Devices).'],
                ['name' => 'smtp.port', 'label' => 'Port', 'type' => 'number', 'rules' => 'nullable|integer|min:1|max:65535'],
                ['name' => 'smtp.encryption', 'label' => 'Criptare', 'type' => 'select', 'options' => ['tls' => 'STARTTLS (port 587)', 'ssl' => 'SSL/TLS (port 465)', 'none' => 'Fără (nerecomandat)'], 'rules' => 'nullable|in:tls,ssl,none'],
                ['name' => 'smtp.username', 'label' => 'Utilizator', 'type' => 'text', 'max' => 190],
                ['name' => 'smtp.password', 'label' => 'Parolă', 'type' => 'secret'],
                ['name' => 'smtp.from_email', 'label' => 'Expeditor – email', 'type' => 'text', 'rules' => 'nullable|email|max:190'],
                ['name' => 'smtp.from_name', 'label' => 'Expeditor – nume', 'type' => 'text', 'max' => 120],
                ['name' => 'smtp.timeout', 'label' => 'Timeout (secunde)', 'type' => 'number', 'rules' => 'nullable|integer|min:3|max:60'],
                ['name' => 'notifications.recipients', 'label' => 'Destinatari notificări cereri noi', 'type' => 'lines', 'hint' => 'Câte o adresă pe rând.'],
            ]],
        ];
    }

    public static function get(string $key): array
    {
        $all = self::all();
        abort_unless(isset($all[$key]), 404);

        return $all[$key] + ['key' => $key];
    }
}
