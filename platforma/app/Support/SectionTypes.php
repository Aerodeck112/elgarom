<?php

namespace App\Support;

/**
 * Registry of page section types. Each type has a label, a list of editable fields
 * (rendered by the admin form builder) and a Blade view in resources/views/sections/.
 *
 * Field types: text, textarea, markdown, url, select, checkbox, media, media_multi, repeater.
 */
class SectionTypes
{
    public static function all(): array
    {
        $heading = ['name' => 'heading', 'label' => 'Titlu secțiune', 'type' => 'text', 'max' => 160];
        $intro = ['name' => 'intro', 'label' => 'Text introductiv', 'type' => 'textarea', 'max' => 800];

        return [
            'scene' => ['label' => 'Scena 3D – textele etapelor (doar Acasă)', 'fields' => [
                ['name' => 'items', 'label' => 'Etapele ilustrației', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'label' => 'Titlu', 'type' => 'text', 'max' => 120],
                    ['name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'max' => 400],
                ]],
                ['name' => 'note', 'label' => 'Notă sub ilustrație', 'type' => 'text', 'max' => 240],
            ]],
            'intro' => ['label' => 'Text cu imagine', 'fields' => [
                $heading,
                ['name' => 'body', 'label' => 'Conținut', 'type' => 'markdown', 'max' => 6000],
                ['name' => 'image', 'label' => 'Imagine', 'type' => 'media'],
                ['name' => 'image_side', 'label' => 'Poziția imaginii', 'type' => 'select', 'options' => ['right' => 'Dreapta', 'left' => 'Stânga']],
            ]],
            'text' => ['label' => 'Text', 'fields' => [
                $heading,
                ['name' => 'body', 'label' => 'Conținut', 'type' => 'markdown', 'max' => 12000],
            ]],
            'features' => ['label' => 'Listă de beneficii / caracteristici', 'fields' => [
                $heading, $intro,
                ['name' => 'items', 'label' => 'Elemente', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'label' => 'Titlu', 'type' => 'text', 'max' => 120],
                    ['name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'max' => 600],
                ]],
            ]],
            'steps' => ['label' => 'Etape (secvență numerotată)', 'fields' => [
                $heading, $intro,
                ['name' => 'items', 'label' => 'Etape', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'label' => 'Titlu etapă', 'type' => 'text', 'max' => 120],
                    ['name' => 'text', 'label' => 'Descriere', 'type' => 'textarea', 'max' => 800],
                ]],
                ['name' => 'note', 'label' => 'Notă', 'type' => 'textarea', 'max' => 500],
            ]],
            'photo_band' => ['label' => 'Fotografie pe toată lățimea', 'fields' => [
                ['name' => 'image', 'label' => 'Imagine', 'type' => 'media'],
                ['name' => 'caption', 'label' => 'Descriere', 'type' => 'text', 'max' => 200],
            ]],
            'gallery' => ['label' => 'Galerie foto', 'fields' => [
                $heading, $intro,
                ['name' => 'images', 'label' => 'Imagini', 'type' => 'media_multi'],
            ]],
            'capabilities' => ['label' => 'Date tehnice confirmate (automat)', 'fields' => [
                $heading, $intro,
                ['name' => 'group', 'label' => 'Grup (gol = toate)', 'type' => 'text', 'max' => 60],
            ]],
            'envelope' => ['label' => 'Desen gabarit piese (automat din capacități)', 'fields' => [
                $heading, $intro,
            ]],
            'durability' => ['label' => 'Durabilitate în timp', 'fields' => [
                $heading, $intro,
                ['name' => 'items', 'label' => 'Factori', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'label' => 'Factor', 'type' => 'text', 'max' => 120],
                    ['name' => 'text', 'label' => 'Explicație', 'type' => 'textarea', 'max' => 600],
                ]],
                ['name' => 'note', 'label' => 'Notă (fără garanții)', 'type' => 'textarea', 'max' => 600],
            ]],
            'cta' => ['label' => 'Îndemn la acțiune', 'fields' => [
                $heading,
                ['name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'max' => 500],
                ['name' => 'button_label', 'label' => 'Buton principal – text', 'type' => 'text', 'max' => 60],
                ['name' => 'button_url', 'label' => 'Buton principal – link', 'type' => 'url'],
                ['name' => 'secondary_label', 'label' => 'Buton secundar – text', 'type' => 'text', 'max' => 60],
                ['name' => 'secondary_url', 'label' => 'Buton secundar – link', 'type' => 'url'],
            ]],
            'services' => ['label' => 'Listă servicii (automat)', 'fields' => [$heading, $intro]],
            'industries' => ['label' => 'Listă aplicații / industrii (automat)', 'fields' => [$heading, $intro]],
            'projects' => ['label' => 'Listă proiecte (automat)', 'fields' => [$heading, $intro]],
            'documents' => ['label' => 'Listă documente publice (automat)', 'fields' => [
                $heading, $intro,
                ['name' => 'category', 'label' => 'Categorie (gol = toate)', 'type' => 'text', 'max' => 40],
            ]],
            'faq' => ['label' => 'Întrebări frecvente (automat)', 'fields' => [
                $heading,
                ['name' => 'category', 'label' => 'Categorie (gol = toate)', 'type' => 'text', 'max' => 60],
            ]],
            'group' => ['label' => 'Argento Group', 'fields' => [
                $heading,
                ['name' => 'body', 'label' => 'Conținut', 'type' => 'markdown', 'max' => 4000],
                ['name' => 'companies', 'label' => 'Companii din grup', 'type' => 'repeater', 'fields' => [
                    ['name' => 'name', 'label' => 'Nume', 'type' => 'text', 'max' => 80],
                    ['name' => 'url', 'label' => 'Website', 'type' => 'url'],
                    ['name' => 'text', 'label' => 'Descriere scurtă', 'type' => 'text', 'max' => 200],
                ]],
            ]],
            'quote_short' => ['label' => 'Formular scurt de contact', 'fields' => [$heading, $intro]],
            'contact' => ['label' => 'Date de contact și hartă', 'fields' => [$heading, $intro]],
        ];
    }

    public static function get(string $type): ?array
    {
        return self::all()[$type] ?? null;
    }

    public static function labels(): array
    {
        return array_map(fn ($t) => $t['label'], self::all());
    }
}
