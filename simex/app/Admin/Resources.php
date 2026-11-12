<?php

namespace App\Admin;

use App\Calc\ModelRegistry;
use App\Models\Entity;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Operation;
use App\Models\Product;
use App\Models\Supplier;
use App\Support\Vocab;

/** Library screens that share the generic list/form controller. */
final class Resources
{
    public static function get(string $key): array
    {
        $all = self::all();
        abort_unless(isset($all[$key]), 404);

        return $all[$key] + ['key' => $key];
    }

    public static function all(): array
    {
        $currencies = fn () => array_combine(config('simex.currencies'), config('simex.currencies'));
        $suppliers = fn () => Supplier::orderBy('name')->pluck('name', 'id')->all();
        $machines = fn () => Machine::orderBy('name')->pluck('name', 'id')->all();
        $models = fn () => array_map(fn ($d) => $d->title, app(ModelRegistry::class)->all());

        return [
            'materials' => [
                'model' => Material::class, 'title' => 'Materiale', 'singular' => 'material', 'view' => 'library.view', 'manage' => 'library.manage',
                'intro' => 'Biblioteca de materiale cu prețuri și istoric. Prețurile sunt introduse de persoanele autorizate; nimic nu este presupus.',
                'search' => ['code', 'name', 'grade'], 'order' => 'name', 'with' => ['supplier'],
                'filters' => ['category' => ['label' => 'Categorie', 'options' => Vocab::MATERIAL_CATEGORIES]],
                'columns' => [
                    'code' => ['label' => 'Cod', 'mono' => true], 'name' => ['label' => 'Denumire'], 'category' => ['label' => 'Categorie', 'vocab' => Vocab::MATERIAL_CATEGORIES],
                    'grade' => ['label' => 'Marcă / standard'], 'price' => ['label' => 'Preț', 'money' => 'currency', 'unit' => 'unit', 'perm' => 'costs.view'],
                    'price_date' => ['label' => 'Data prețului', 'date' => true, 'perm' => 'costs.view'], 'is_active' => ['label' => 'Activ', 'bool' => true],
                ],
                'fields' => [
                    ['name' => 'code', 'label' => 'Cod', 'type' => 'text', 'rules' => ['required', 'string', 'max:60', 'unique'], 'span' => 4],
                    ['name' => 'name', 'label' => 'Denumire', 'type' => 'text', 'rules' => 'required|string|max:255', 'span' => 8],
                    ['name' => 'category', 'label' => 'Categorie', 'type' => 'select', 'options' => Vocab::MATERIAL_CATEGORIES, 'rules' => ['required'], 'span' => 4],
                    ['name' => 'grade', 'label' => 'Marcă / standard', 'type' => 'text', 'max' => 80, 'span' => 4],
                    ['name' => 'form', 'label' => 'Formă (tablă, bară, placă …)', 'type' => 'text', 'max' => 60, 'span' => 4],
                    ['name' => 'density', 'label' => 'Densitate (kg/dm³)', 'type' => 'decimal', 'span' => 3],
                    ['name' => 'unit', 'label' => 'Unitate de preț', 'type' => 'select', 'options' => ['kg' => 'kg', 'buc' => 'buc', 'm' => 'm', 'm2' => 'm²'], 'rules' => ['required'], 'span' => 3],
                    ['name' => 'price', 'label' => 'Preț pe unitate', 'type' => 'decimal', 'span' => 3, 'perm' => 'rates.edit'],
                    ['name' => 'currency', 'label' => 'Monedă', 'type' => 'select', 'options' => $currencies, 'rules' => ['required'], 'span' => 3, 'perm' => 'rates.edit'],
                    ['name' => 'price_date', 'label' => 'Data prețului', 'type' => 'date', 'span' => 4, 'perm' => 'rates.edit'],
                    ['name' => 'supplier_id', 'label' => 'Furnizor', 'type' => 'select', 'options' => $suppliers, 'span' => 8],
                    ['name' => 'notes', 'label' => 'Observații', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Activ', 'type' => 'checkbox', 'span' => 12],
                ],
                'history' => true,
            ],
            'machines' => [
                'model' => Machine::class, 'title' => 'Utilaje', 'singular' => 'utilaj', 'view' => 'library.view', 'manage' => 'library.manage',
                'intro' => 'Tariful orar transformă orele estimate de calculatoare în cost. Până la introducerea lui, calculatoarele afișează doar orele.',
                'search' => ['code', 'name', 'process'], 'order' => 'name', 'with' => ['entity'],
                'columns' => [
                    'code' => ['label' => 'Cod', 'mono' => true], 'name' => ['label' => 'Utilaj'], 'process' => ['label' => 'Proces'],
                    'entity.name' => ['label' => 'Companie'], 'hourly_rate' => ['label' => 'Tarif orar', 'money' => 'currency', 'perm' => 'costs.view', 'empty' => 'neconfigurat'],
                    'rate_date' => ['label' => 'Valabil din', 'date' => true, 'perm' => 'costs.view'], 'is_active' => ['label' => 'Activ', 'bool' => true],
                ],
                'fields' => [
                    ['name' => 'code', 'label' => 'Cod', 'type' => 'text', 'rules' => ['required', 'string', 'max:60', 'unique'], 'span' => 4],
                    ['name' => 'name', 'label' => 'Denumire', 'type' => 'text', 'rules' => 'required|string|max:255', 'span' => 8],
                    ['name' => 'process', 'label' => 'Proces', 'type' => 'text', 'max' => 80, 'span' => 4],
                    ['name' => 'entity_id', 'label' => 'Companie', 'type' => 'select', 'options' => fn () => Entity::orderBy('sort')->pluck('name', 'id')->all(), 'span' => 4],
                    ['name' => 'calc_model_key', 'label' => 'Calculator asociat', 'type' => 'select', 'options' => $models, 'span' => 4, 'hint' => 'Orele acestui calculator se înmulțesc cu tariful utilajului.'],
                    ['name' => 'hourly_rate', 'label' => 'Tarif orar', 'type' => 'decimal', 'span' => 4, 'perm' => 'rates.edit'],
                    ['name' => 'currency', 'label' => 'Monedă', 'type' => 'select', 'options' => $currencies, 'rules' => ['required'], 'span' => 4, 'perm' => 'rates.edit'],
                    ['name' => 'rate_date', 'label' => 'Tarif valabil din', 'type' => 'date', 'span' => 4, 'perm' => 'rates.edit'],
                    ['name' => 'capacity', 'label' => 'Capacități și limite', 'type' => 'textarea', 'hint' => 'Curse, gabarit, sarcină maximă – după documentația utilajului.'],
                    ['name' => 'notes', 'label' => 'Observații', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Activ', 'type' => 'checkbox', 'span' => 12],
                ],
                'history' => true,
            ],
            'operations' => [
                'model' => Operation::class, 'title' => 'Operații tehnologice', 'singular' => 'operație', 'view' => 'library.view', 'manage' => 'library.manage',
                'intro' => 'Catalogul operațiilor folosite în oferte. O operație cu «Calculator asociat» are formula validată din Excel; celelalte au doar un tarif introdus manual.',
                'search' => ['code', 'name', 'category'], 'order' => 'sort', 'with' => ['machine'],
                'columns' => [
                    'code' => ['label' => 'Cod', 'mono' => true], 'name' => ['label' => 'Operație'], 'category' => ['label' => 'Categorie'], 'machine.name' => ['label' => 'Utilaj'],
                    'cost_basis' => ['label' => 'Bază de cost', 'vocab' => Vocab::COST_BASES], 'rate' => ['label' => 'Tarif', 'money' => 'currency', 'perm' => 'costs.view', 'empty' => 'neconfigurat'],
                    'calc_model_key' => ['label' => 'Formulă', 'formula' => true], 'is_subcontracted' => ['label' => 'Subcontractat', 'bool' => true],
                ],
                'fields' => [
                    ['name' => 'code', 'label' => 'Cod', 'type' => 'text', 'rules' => ['required', 'string', 'max:60', 'unique'], 'span' => 4],
                    ['name' => 'name', 'label' => 'Denumire', 'type' => 'text', 'rules' => 'required|string|max:255', 'span' => 8],
                    ['name' => 'category', 'label' => 'Categorie', 'type' => 'text', 'max' => 60, 'span' => 4],
                    ['name' => 'machine_id', 'label' => 'Utilaj', 'type' => 'select', 'options' => $machines, 'span' => 4],
                    ['name' => 'calc_model_key', 'label' => 'Calculator asociat', 'type' => 'select', 'options' => $models, 'span' => 4],
                    ['name' => 'cost_basis', 'label' => 'Bază de cost', 'type' => 'select', 'options' => Vocab::COST_BASES, 'rules' => ['required'], 'span' => 4],
                    ['name' => 'rate', 'label' => 'Tarif pe unitate', 'type' => 'decimal', 'span' => 4, 'perm' => 'rates.edit'],
                    ['name' => 'currency', 'label' => 'Monedă', 'type' => 'select', 'options' => $currencies, 'rules' => ['required'], 'span' => 4, 'perm' => 'rates.edit'],
                    ['name' => 'sort', 'label' => 'Ordine', 'type' => 'number', 'span' => 4],
                    ['name' => 'is_subcontracted', 'label' => 'Operație subcontractată', 'type' => 'checkbox', 'span' => 4],
                    ['name' => 'is_active', 'label' => 'Activă', 'type' => 'checkbox', 'span' => 4],
                    ['name' => 'notes', 'label' => 'Observații', 'type' => 'textarea'],
                ],
                'history' => true,
            ],
            'suppliers' => [
                'model' => Supplier::class, 'title' => 'Furnizori', 'singular' => 'furnizor', 'view' => 'library.view', 'manage' => 'library.manage',
                'search' => ['name', 'category', 'city', 'contact_name'], 'order' => 'name',
                'columns' => [
                    'name' => ['label' => 'Furnizor'], 'category' => ['label' => 'Categorie'], 'country' => ['label' => 'Țară', 'vocab' => Vocab::countries()], 'city' => ['label' => 'Localitate'],
                    'contact_name' => ['label' => 'Contact'], 'email' => ['label' => 'Email'], 'phone' => ['label' => 'Telefon'], 'is_active' => ['label' => 'Activ', 'bool' => true],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Denumire', 'type' => 'text', 'rules' => 'required|string|max:255', 'span' => 8],
                    ['name' => 'category', 'label' => 'Categorie (materiale, tratamente, scule …)', 'type' => 'text', 'max' => 120, 'span' => 4],
                    ['name' => 'country', 'label' => 'Țară', 'type' => 'select', 'options' => Vocab::countries(), 'span' => 4],
                    ['name' => 'city', 'label' => 'Localitate', 'type' => 'text', 'max' => 120, 'span' => 4],
                    ['name' => 'tax_id', 'label' => 'Cod fiscal', 'type' => 'text', 'max' => 40, 'span' => 4],
                    ['name' => 'contact_name', 'label' => 'Persoană de contact', 'type' => 'text', 'max' => 160, 'span' => 4],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'span' => 4],
                    ['name' => 'phone', 'label' => 'Telefon', 'type' => 'text', 'max' => 40, 'span' => 4],
                    ['name' => 'website', 'label' => 'Website', 'type' => 'url', 'span' => 6],
                    ['name' => 'payment_terms', 'label' => 'Condiții de plată', 'type' => 'text', 'max' => 160, 'span' => 6],
                    ['name' => 'notes', 'label' => 'Observații', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Activ', 'type' => 'checkbox', 'span' => 12],
                ],
            ],
            'products' => [
                'model' => Product::class, 'title' => 'Produse și proiecte', 'singular' => 'produs', 'view' => 'crm.view', 'manage' => 'rfq.manage',
                'intro' => 'Piesele și reperele clienților: cod, desen, revizie, material și dimensiuni. Se refolosesc în cereri, calcule și oferte.',
                'search' => ['code', 'name', 'drawing_no', 'project'], 'order' => 'code', 'with' => ['company', 'material'],
                'filters' => ['kind' => ['label' => 'Tip', 'options' => ['piesa' => 'Piesă', 'scula' => 'Ștanță / sculă', 'zincare' => 'Reper pentru zincare']]],
                'columns' => [
                    'code' => ['label' => 'Cod', 'mono' => true], 'name' => ['label' => 'Denumire'], 'company.name' => ['label' => 'Client'], 'project' => ['label' => 'Proiect'],
                    'drawing_no' => ['label' => 'Desen'], 'revision' => ['label' => 'Rev.'], 'material.name' => ['label' => 'Material'], 'weight_net' => ['label' => 'Greutate netă (kg)', 'number' => 3],
                ],
                'fields' => [
                    ['name' => 'kind', 'label' => 'Tip', 'type' => 'select', 'options' => ['piesa' => 'Piesă', 'scula' => 'Ștanță / sculă', 'zincare' => 'Reper pentru zincare'], 'rules' => ['required'], 'span' => 4],
                    ['name' => 'code', 'label' => 'Cod piesă', 'type' => 'text', 'rules' => 'required|string|max:80', 'span' => 4],
                    ['name' => 'name', 'label' => 'Denumire', 'type' => 'text', 'rules' => 'required|string|max:255', 'span' => 4],
                    ['name' => 'company_id', 'label' => 'Client', 'type' => 'select', 'options' => fn () => \App\Models\Company::orderBy('name')->pluck('name', 'id')->all(), 'span' => 6],
                    ['name' => 'project', 'label' => 'Proiect', 'type' => 'text', 'span' => 6],
                    ['name' => 'drawing_no', 'label' => 'Număr desen', 'type' => 'text', 'max' => 80, 'span' => 4],
                    ['name' => 'revision', 'label' => 'Revizie', 'type' => 'text', 'max' => 20, 'span' => 4],
                    ['name' => 'material_id', 'label' => 'Material', 'type' => 'select', 'options' => fn () => Material::orderBy('name')->pluck('name', 'id')->all(), 'span' => 4],
                    ['name' => 'thickness', 'label' => 'Grosime (mm)', 'type' => 'decimal', 'span' => 3],
                    ['name' => 'length', 'label' => 'Lungime (mm)', 'type' => 'decimal', 'span' => 3],
                    ['name' => 'width', 'label' => 'Lățime (mm)', 'type' => 'decimal', 'span' => 3],
                    ['name' => 'height', 'label' => 'Înălțime (mm)', 'type' => 'decimal', 'span' => 3],
                    ['name' => 'weight_gross', 'label' => 'Greutate brută (kg)', 'type' => 'decimal', 'span' => 3],
                    ['name' => 'weight_net', 'label' => 'Greutate netă (kg)', 'type' => 'decimal', 'span' => 3],
                    ['name' => 'annual_quantity', 'label' => 'Cantitate anuală estimată', 'type' => 'number', 'span' => 3],
                    ['name' => 'lot_size', 'label' => 'Dimensiunea lotului', 'type' => 'number', 'span' => 3],
                    ['name' => 'notes', 'label' => 'Observații', 'type' => 'textarea'],
                ],
            ],
        ];
    }
}
