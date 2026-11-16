<?php

namespace App\Admin;

/** Settings forms. Keys map 1:1 to rows in the `settings` table. Type "secret" is stored encrypted. */
class SettingGroups
{
    public static function all(): array
    {
        return [
            'general' => ['label' => 'Configurări generale', 'perm' => 'settings', 'intro' => 'Valorile implicite ale ofertelor noi. Fiecare ofertă le poate schimba; ofertele deja emise nu sunt afectate.', 'fields' => [
                ['name' => 'app.name', 'label' => 'Numele platformei', 'type' => 'text', 'max' => 80, 'hint' => 'Afișat în meniu și în titlul paginilor. Gol = GRUP SIMEX.'],
                ['name' => 'quotes.validity_days', 'label' => 'Valabilitate implicită a ofertei (zile)', 'type' => 'number', 'rules' => 'nullable|integer|min:1|max:365'],
                ['name' => 'quotes.currency', 'label' => 'Monedă implicită', 'type' => 'select', 'options' => fn () => array_combine(config('simex.currencies'), config('simex.currencies'))],
                ['name' => 'quotes.vat_rate', 'label' => 'Cotă TVA implicită (%)', 'type' => 'decimal', 'rules' => 'nullable|numeric|min:0|max:50', 'hint' => 'Introduceți cota aplicabilă. Pentru livrări intracomunitare se setează 0 pe ofertă.'],
                ['name' => 'quotes.exchange_rate', 'label' => 'Curs RON/EUR folosit la ofertare', 'type' => 'decimal', 'rules' => 'nullable|numeric|min:0.0001|max:1000', 'hint' => 'Se copiază în fiecare ofertă nouă și rămâne înghețat în ea.'],
                ['name' => 'quotes.exchange_rate_date', 'label' => 'Data cursului', 'type' => 'date'],
                ['name' => 'quotes.fx_auto', 'label' => 'Preia automat cursul EUR de la BNR (zilnic, prin cron)', 'type' => 'checkbox', 'hint' => 'Ofertele deja create își păstrează cursul. Butonul «Actualizează acum» este în antet, pe insigna EUR.'],
                ['name' => 'quotes.min_margin', 'label' => 'Prag minim de marjă (%)', 'type' => 'decimal', 'rules' => 'nullable|numeric|min:0|max:100', 'hint' => 'Sub acest prag oferta cere aprobarea unui rol cu permisiunea «Aprobarea ofertelor». Gol = fără prag.'],
                ['name' => 'quotes.max_discount', 'label' => 'Discount maxim fără aprobare (%)', 'type' => 'decimal', 'rules' => 'nullable|numeric|min:0|max:100'],
                ['name' => 'quotes.link_days', 'label' => 'Valabilitatea linkului securizat pentru client (zile)', 'type' => 'number', 'rules' => 'nullable|integer|min:1|max:120'],
            ]],
            'smtp' => ['label' => 'SMTP și email', 'perm' => 'smtp', 'fields' => [
                ['name' => 'smtp.host', 'label' => 'Server SMTP', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9.\-]+$/i'], 'hint' => 'Din cPanel → Conturi email → Connect Devices.'],
                ['name' => 'smtp.port', 'label' => 'Port', 'type' => 'number', 'rules' => 'nullable|integer|min:1|max:65535'],
                ['name' => 'smtp.encryption', 'label' => 'Criptare', 'type' => 'select', 'options' => ['tls' => 'STARTTLS (port 587)', 'ssl' => 'SSL/TLS (port 465)', 'none' => 'Fără (nerecomandat)']],
                ['name' => 'smtp.username', 'label' => 'Utilizator', 'type' => 'text', 'max' => 190],
                ['name' => 'smtp.password', 'label' => 'Parolă', 'type' => 'secret'],
                ['name' => 'smtp.from_email', 'label' => 'Expeditor – email', 'type' => 'email'],
                ['name' => 'smtp.from_name', 'label' => 'Expeditor – nume', 'type' => 'text', 'max' => 120],
                ['name' => 'smtp.reply_to', 'label' => 'Email de răspuns', 'type' => 'email', 'hint' => 'Gol = răspunsurile ajung la utilizatorul care trimite oferta.'],
                ['name' => 'smtp.timeout', 'label' => 'Timeout (secunde)', 'type' => 'number', 'rules' => 'nullable|integer|min:3|max:60'],
                ['name' => 'smtp.signature', 'label' => 'Semnătură implicită', 'type' => 'textarea', 'max' => 1000, 'hint' => 'Text simplu, adăugat sub mesajele trimise din platformă.'],
            ]],
            'ai' => ['label' => 'API Anthropic Claude', 'perm' => 'api.keys', 'intro' => 'Cheia este folosită doar de server, niciodată trimisă în browser. Cheia Claude nu oferă căutare pe internet: prospectarea are nevoie separat de o sursă de date aprobată.', 'fields' => [
                ['name' => 'ai.api_key', 'label' => 'Cheie API Anthropic', 'type' => 'secret'],
                ['name' => 'ai.model', 'label' => 'Model', 'type' => 'select', 'options' => fn () => config('simex.ai.models')],
                ['name' => 'ai.allow_confidential', 'label' => 'Permite trimiterea către AI a textelor din oferte (fără costuri interne)', 'type' => 'checkbox', 'hint' => 'Dezactivat implicit. Costurile interne, marjele și documentele tehnice nu sunt trimise niciodată.'],
                ['name' => 'ai.monthly_budget_usd', 'label' => 'Buget lunar maxim (USD)', 'type' => 'decimal', 'rules' => 'nullable|numeric|min:0|max:100000'],
            ]],
            'site' => ['label' => 'Website public', 'perm' => 'site', 'intro' => 'Textele paginii publice. Companiile grupului, siglele și linkurile lor se administrează la «Companiile grupului».', 'fields' => [
                ['name' => 'site.title', 'label' => 'Titlu', 'type' => 'text', 'max' => 120],
                ['name' => 'site.subtitle', 'label' => 'Subtitlu', 'type' => 'textarea', 'max' => 300, 'rows' => 2],
                ['name' => 'site.intro', 'label' => 'Text de prezentare', 'type' => 'textarea', 'max' => 1500, 'rows' => 6],
                ['name' => 'site.contact_email', 'label' => 'Email de contact afișat', 'type' => 'email'],
                ['name' => 'site.contact_phone', 'label' => 'Telefon afișat', 'type' => 'text', 'max' => 40],
                ['name' => 'site.meta_description', 'label' => 'Descriere pentru motoarele de căutare', 'type' => 'textarea', 'max' => 170, 'rows' => 2],
                ['name' => 'site.privacy', 'label' => 'Politica de confidențialitate (Markdown)', 'type' => 'textarea', 'max' => 30000, 'rows' => 14, 'hint' => 'Pagina este publicată doar după ce acest câmp este completat.'],
                ['name' => 'site.indexable', 'label' => 'Permite indexarea de către motoarele de căutare', 'type' => 'checkbox', 'hint' => 'Bifați după ce datele juridice și siglele sunt confirmate.'],
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
