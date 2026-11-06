<?php

namespace App\Support;

/** schema.org data built only from stored, real company information. */
class StructuredData
{
    public static function organization(): array
    {
        $phones = setting('company.phones', []);
        $emails = setting('company.emails', []);
        $logo = media_item(setting('company.logo_id'));
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            '@id' => url('/').'#organizatie',
            'name' => setting('company.name', 'ELGAROM'),
            'url' => url('/'),
            'inLanguage' => app()->getLocale(),
            'description' => lsetting('company.tagline'),
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => setting('company.street'),
                'addressLocality' => setting('company.city'),
                'addressRegion' => setting('company.county'),
                'postalCode' => setting('company.postal_code'),
                'addressCountry' => setting('company.country', 'RO'),
            ]),
        ];
        if (setting('company.legal_name')) {
            $data['legalName'] = setting('company.legal_name');
        }
        if (setting('company.cui')) {
            $data['vatID'] = setting('company.cui');
        }
        if ($logo) {
            $data['logo'] = $logo->url();
            $data['image'] = $logo->url();
        }
        if (! empty($phones)) {
            $data['telephone'] = tel_href($phones[0]['number']) === 'tel:+' ? null : substr(tel_href($phones[0]['number']), 4);
            $data['contactPoint'] = array_map(fn ($p) => [
                '@type' => 'ContactPoint',
                'telephone' => substr(tel_href($p['number']), 4),
                'contactType' => $p['label'] === 'Tehnic' ? 'technical support' : 'sales',
                'availableLanguage' => ['ro'],
            ], $phones);
        }
        if (! empty($emails)) {
            $data['email'] = $emails[0]['email'];
        }
        if (setting('group.name')) {
            $data['parentOrganization'] = array_filter(['@type' => 'Organization', 'name' => setting('group.name'), 'url' => setting('group.url')]);
        }

        return array_filter($data, fn ($v) => $v !== null && $v !== []);
    }

    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn ($it, $i) => [
                '@type' => 'ListItem', 'position' => $i + 1, 'name' => $it[0], 'item' => $it[1],
            ], $items, array_keys($items)),
        ];
    }

    public static function faq($faqs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn ($f) => [
                '@type' => 'Question', 'name' => $f->question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f->answer],
            ])->values()->all(),
        ];
    }

    public static function service($service): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $service->title,
            'description' => $service->summary,
            'provider' => ['@id' => url('/').'#organizatie'],
            'areaServed' => 'RO',
            'url' => service_url($service),
        ];
    }
}
