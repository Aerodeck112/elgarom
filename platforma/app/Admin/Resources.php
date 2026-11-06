<?php

namespace App\Admin;

use App\Models;

/**
 * Declarative admin resources. Each entry drives list, form, validation and permissions.
 * Field types: text, textarea, markdown, number, select, checkbox, media, media_multi, file, date, url, slug.
 */
class Resources
{
    public static function all(): array
    {
        $sort = ['name' => 'sort', 'label' => 'Ordine', 'type' => 'number', 'rules' => 'nullable|integer|min:0|max:9999', 'hint' => 'Număr mic = mai sus în listă.'];
        $pub = fn ($label = 'Publicat pe site') => ['name' => 'is_published', 'label' => $label, 'type' => 'checkbox'];

        return [
            'services' => [
                'model' => Models\Service::class, 'label' => 'Servicii', 'singular' => 'serviciu', 'perm' => 'content',
                'columns' => ['title' => 'Titlu', 'slug' => 'Adresă', 'is_published' => 'Publicat'], 'search' => ['title', 'summary'], 'order' => 'sort',
                'public' => fn ($m) => service_url($m),
                'fields' => [
                    ['name' => 'title', 'label' => 'Titlu', 'type' => 'text', 'rules' => 'required|string|max:160'],
                    ['name' => 'slug', 'label' => 'Adresă URL', 'type' => 'slug', 'from' => 'title', 'rules' => 'nullable|string|max:120|regex:/^[a-z0-9\-]+$/', 'hint' => 'Doar litere mici, cifre și cratime. Se generează din titlu dacă rămâne gol.'],
                    ['name' => 'summary', 'label' => 'Rezumat', 'type' => 'textarea', 'rules' => 'nullable|string|max:400'],
                    ['name' => 'body', 'label' => 'Conținut', 'type' => 'markdown', 'rules' => 'nullable|string|max:20000'],
                    ['name' => 'image_id', 'label' => 'Imagine', 'type' => 'media', 'rules' => 'nullable|integer|exists:media,id'],
                    $pub(), $sort,
                ],
            ],
            'capabilities' => [
                'model' => Models\Capability::class, 'label' => 'Capacități tehnice', 'singular' => 'capacitate', 'perm' => 'content',
                'columns' => ['label' => 'Denumire', 'value' => 'Valoare', 'unit' => 'UM', 'is_confirmed' => 'Confirmat', 'show_on_home' => 'Pe Acasă'], 'search' => ['label', 'value', 'group'], 'order' => 'sort',
                'intro' => 'Pe site apar doar valorile marcate „Confirmat”, cu sursă. Valorile lipsă rămân aici ca listă de completat și nu se publică.',
                'fields' => [
                    ['name' => 'label', 'label' => 'Denumire', 'type' => 'text', 'rules' => 'required|string|max:160'],
                    ['name' => 'value', 'label' => 'Valoare', 'type' => 'text', 'rules' => 'nullable|string|max:190|required_if:is_confirmed,1', 'hint' => 'Pentru gabaritul pe dispozitiv folosiți formatul „1900 × 1200 × 450”.'],
                    ['name' => 'unit', 'label' => 'Unitate de măsură', 'type' => 'text', 'rules' => 'nullable|string|max:30'],
                    ['name' => 'source', 'label' => 'Sursa informației', 'type' => 'text', 'rules' => 'nullable|string|max:190|required_if:is_confirmed,1', 'hint' => 'Obligatorie pentru valorile confirmate (ex. „Fișa tehnică a liniei, rev. 2026”).'],
                    ['name' => 'note', 'label' => 'Notă publică', 'type' => 'textarea', 'rules' => 'nullable|string|max:500'],
                    ['name' => 'group', 'label' => 'Grup', 'type' => 'text', 'rules' => 'required|string|max:60'],
                    ['name' => 'key', 'label' => 'Cheie tehnică', 'type' => 'text', 'rules' => 'nullable|string|max:60|regex:/^[a-z0-9_]+$/', 'hint' => '„rack_envelope” și „barrel_load” alimentează desenul de gabarit.'],
                    ['name' => 'is_confirmed', 'label' => 'Confirmat – poate fi publicat', 'type' => 'checkbox'],
                    ['name' => 'show_on_home', 'label' => 'Afișat în primul ecran de pe Acasă', 'type' => 'checkbox'],
                    $sort,
                ],
            ],
            'industries' => [
                'model' => Models\Industry::class, 'label' => 'Aplicații și industrii', 'singular' => 'industrie', 'perm' => 'content',
                'columns' => ['title' => 'Denumire', 'is_published' => 'Publicat'], 'search' => ['title'], 'order' => 'sort',
                'intro' => 'Publicați doar industriile pe care Elgarom le deservește efectiv. Exemplele inițiale sunt nepublicate.',
                'fields' => [
                    ['name' => 'title', 'label' => 'Denumire', 'type' => 'text', 'rules' => 'required|string|max:160'],
                    ['name' => 'description', 'label' => 'Descriere', 'type' => 'textarea', 'rules' => 'nullable|string|max:800'],
                    ['name' => 'image_id', 'label' => 'Imagine', 'type' => 'media', 'rules' => 'nullable|integer|exists:media,id'],
                    $pub(), $sort,
                ],
            ],
            'projects' => [
                'model' => Models\Project::class, 'label' => 'Proiecte', 'singular' => 'proiect', 'perm' => 'content',
                'columns' => ['title' => 'Titlu', 'client' => 'Client', 'is_published' => 'Publicat'], 'search' => ['title', 'client'], 'order' => 'sort',
                'public' => fn ($m) => project_url($m),
                'fields' => [
                    ['name' => 'title', 'label' => 'Titlu', 'type' => 'text', 'rules' => 'required|string|max:160'],
                    ['name' => 'slug', 'label' => 'Adresă URL', 'type' => 'slug', 'from' => 'title', 'rules' => 'nullable|string|max:120|regex:/^[a-z0-9\-]+$/'],
                    ['name' => 'client', 'label' => 'Client', 'type' => 'text', 'rules' => 'nullable|string|max:160'],
                    ['name' => 'client_consent', 'label' => 'Clientul a fost de acord cu publicarea numelui', 'type' => 'checkbox', 'hint' => 'Fără acord, numele clientului nu apare pe site.'],
                    ['name' => 'summary', 'label' => 'Rezumat', 'type' => 'textarea', 'rules' => 'nullable|string|max:400'],
                    ['name' => 'body', 'label' => 'Descriere', 'type' => 'markdown', 'rules' => 'nullable|string|max:20000'],
                    ['name' => 'cover_id', 'label' => 'Imagine principală', 'type' => 'media', 'rules' => 'nullable|integer|exists:media,id'],
                    ['name' => 'gallery', 'label' => 'Galerie', 'type' => 'media_multi', 'rules' => 'nullable|array|max:40', 'item_rules' => 'integer|exists:media,id'],
                    $pub(), $sort,
                ],
            ],
            'documents' => [
                'model' => Models\Document::class, 'label' => 'Calitate și documente', 'singular' => 'document', 'perm' => 'content',
                'columns' => ['title' => 'Titlu', 'category' => 'Categorie', 'valid_until' => 'Valabil până la', 'is_public' => 'Public'], 'search' => ['title'], 'order' => 'sort',
                'intro' => 'Documentele sunt stocate privat. Doar cele marcate „Public” pot fi descărcate de pe site.',
                'fields' => [
                    ['name' => 'title', 'label' => 'Titlu', 'type' => 'text', 'rules' => 'required|string|max:190'],
                    ['name' => 'description', 'label' => 'Descriere', 'type' => 'textarea', 'rules' => 'nullable|string|max:600'],
                    ['name' => 'category', 'label' => 'Categorie', 'type' => 'select', 'options' => ['certificat' => 'Certificat', 'fisa_tehnica' => 'Fișă tehnică', 'politica' => 'Politică', 'document' => 'Alt document'], 'rules' => 'required|in:certificat,fisa_tehnica,politica,document'],
                    ['name' => 'file', 'label' => 'Fișier (PDF, JPG, PNG)', 'type' => 'file', 'rules' => 'nullable|file|max:20480'],
                    ['name' => 'valid_until', 'label' => 'Valabil până la', 'type' => 'date', 'rules' => 'nullable|date'],
                    ['name' => 'is_public', 'label' => 'Public (descărcabil de pe site)', 'type' => 'checkbox'],
                    $sort,
                ],
            ],
            'faqs' => [
                'model' => Models\Faq::class, 'label' => 'Întrebări frecvente', 'singular' => 'întrebare', 'perm' => 'content',
                'columns' => ['question' => 'Întrebare', 'category' => 'Categorie', 'is_published' => 'Publicat'], 'search' => ['question', 'answer'], 'order' => 'sort',
                'fields' => [
                    ['name' => 'question', 'label' => 'Întrebare', 'type' => 'text', 'rules' => 'required|string|max:255'],
                    ['name' => 'answer', 'label' => 'Răspuns', 'type' => 'markdown', 'rules' => 'required|string|max:4000'],
                    ['name' => 'category', 'label' => 'Categorie', 'type' => 'text', 'rules' => 'required|string|max:60'],
                    $pub(), $sort,
                ],
            ],
            'menu_items' => [
                'model' => Models\MenuItem::class, 'label' => 'Meniuri', 'singular' => 'element de meniu', 'perm' => 'content',
                'columns' => ['menu' => 'Meniu', 'label' => 'Text', 'url' => 'Link', 'is_visible' => 'Vizibil'], 'search' => ['label', 'url'], 'order' => 'sort', 'filter' => ['menu' => ['header' => 'Antet', 'footer' => 'Subsol']],
                'fields' => [
                    ['name' => 'menu', 'label' => 'Meniu', 'type' => 'select', 'options' => ['header' => 'Antet', 'footer' => 'Subsol'], 'rules' => 'required|in:header,footer'],
                    ['name' => 'label', 'label' => 'Text', 'type' => 'text', 'rules' => 'required|string|max:80'],
                    ['name' => 'url', 'label' => 'Link', 'type' => 'url', 'rules' => ['required', 'string', 'max:500', 'regex:/^(https?:\/\/|\/|mailto:|tel:|#)/'], 'hint' => 'Ex. /servicii, /de/leistungen sau https://… – folosiți adresa paginii în limba meniului.'],
                    ['name' => 'is_button', 'label' => 'Afișat ca buton', 'type' => 'checkbox'],
                    ['name' => 'is_visible', 'label' => 'Vizibil', 'type' => 'checkbox'],
                    $sort,
                ],
            ],
            'redirects' => [
                'model' => Models\Redirect::class, 'label' => 'Redirect-uri', 'singular' => 'redirect', 'perm' => 'seo',
                'columns' => ['from_path' => 'De la', 'to_url' => 'Către', 'status_code' => 'Cod', 'hits' => 'Accesări', 'is_active' => 'Activ'], 'search' => ['from_path', 'to_url'], 'order' => 'from_path',
                'intro' => 'Redirect-urile se aplică doar adreselor care nu există pe site. 301 = mutat permanent, 410 = eliminat.',
                'fields' => [
                    ['name' => 'from_path', 'label' => 'Adresa veche', 'type' => 'text', 'rules' => ['required', 'string', 'max:500', 'regex:/^\//'], 'hint' => 'Începe cu /, fără domeniu. Ex. /despre-noi'],
                    ['name' => 'to_url', 'label' => 'Adresa nouă', 'type' => 'url', 'rules' => ['nullable', 'required_unless:status_code,410', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/']],
                    ['name' => 'status_code', 'label' => 'Tip', 'type' => 'select', 'options' => ['301' => '301 – mutat permanent', '302' => '302 – temporar', '410' => '410 – eliminat'], 'rules' => 'required|in:301,302,410'],
                    ['name' => 'is_active', 'label' => 'Activ', 'type' => 'checkbox'],
                ],
            ],
            'knowledge' => [
                'model' => Models\KnowledgeEntry::class, 'label' => 'Bază de cunoștințe AI', 'singular' => 'intrare', 'perm' => 'ai.knowledge', 'localized' => false,
                'columns' => ['title' => 'Titlu', 'source_label' => 'Sursă', 'reviewed_at' => 'Verificat la', 'is_active' => 'Activ'], 'search' => ['title', 'content'], 'order' => 'sort',
                'intro' => 'Asistentul AI răspunde doar pe baza intrărilor active de aici și a conținutului publicat. Fiecare salvare marchează intrarea ca verificată de dumneavoastră.',
                'fields' => [
                    ['name' => 'title', 'label' => 'Titlu', 'type' => 'text', 'rules' => 'required|string|max:160'],
                    ['name' => 'content', 'label' => 'Informație aprobată', 'type' => 'textarea', 'rules' => 'required|string|max:4000', 'rows' => 8],
                    ['name' => 'source_label', 'label' => 'Sursă', 'type' => 'text', 'rules' => 'nullable|string|max:190'],
                    ['name' => 'source_url', 'label' => 'Link sursă', 'type' => 'url', 'rules' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/']],
                    ['name' => 'is_active', 'label' => 'Activ (folosit de asistent)', 'type' => 'checkbox'],
                    $sort,
                ],
            ],
        ];
    }

    public static function get(string $key): array
    {
        $all = self::all();
        abort_unless(isset($all[$key]), 404);

        return $all[$key] + ['key' => $key];
    }
}
