<?php

namespace Database\Seeders;

use App\Calc\ModelRegistry;
use App\Models\Entity;
use App\Models\Machine;
use App\Models\Operation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Reference data that is not commercial: the group's companies (names, activities and websites as supplied),
 * the machines named in the costing workbooks and the catalogue of operations. No prices, tariffs or legal data.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $entities = [
            ['grup-simex', 'GRUP SIMEX', 'Soluții industriale integrate', null, 'https://grupsimex.ro', '#ffc40e', 'GS', null],
            ['argento', 'ARGENTO GROUP', 'Componente și piese metalice', 'Producția de piese și componente metalice: debitare laser, îndoire, sudură și finisaje.', 'https://www.argento-group.com', '#ffc40e', 'AG', 'argento.png'],
            ['elgarom', 'ELGAROM', 'Zincare electrolitică și tratamente de suprafață', 'Zincare electrolitică pe dispozitive și în tamburi, pasivare și top coating.', 'https://elgarom.ro', '#14ad95', 'EL', 'elgarom.png'],
            ['fepo', 'FEPO STEEL ENGINEERING', 'Proiectare și fabricație ștanțe și matrițe', 'Proiectarea și fabricarea ștanțelor, matrițelor și sculelor industriale.', 'https://fepo.ro', '#ffc40e', 'FP', null],
        ];
        foreach ($entities as $i => [$code, $name, $activity, $description, $website, $accent, $prefix, $logo]) {
            $entity = Entity::firstOrCreate(['code' => $code], [
                'name' => $name, 'activity' => $activity, 'description' => $description, 'website' => $website, 'accent_color' => $accent,
                'quote_prefix' => $prefix, 'is_issuer' => true, 'show_on_site' => true, 'sort' => $i,
            ]);
            // Official logos available from verified sources; the others are uploaded by an administrator.
            if ($logo && ! $entity->logo_path && is_file(database_path('seed-logos/'.$logo))) {
                Storage::disk('local')->put('logos/'.$logo, file_get_contents(database_path('seed-logos/'.$logo)));
                $entity->update(['logo_path' => 'logos/'.$logo, 'logo_on_dark' => $code === 'argento']);
            }
        }

        $fepo = Entity::where('code', 'fepo')->value('id');
        foreach (app(ModelRegistry::class)->all() as $def) {
            if ($def->machine) {
                Machine::firstOrCreate(['code' => $def->machine['code']], ['name' => $def->machine['name'], 'process' => $def->process, 'calc_model_key' => $def->key(), 'entity_id' => $fepo]);
            }
        }

        $operations = [
            ['DEBITARE-LASER', 'Debitare laser', 'Debitare', 'laser-abkant-finisaje'], ['INDOIRE', 'Îndoire (abkant)', 'Îndoire', 'laser-abkant-finisaje'], ['SUDARE', 'Sudare', 'Sudare', 'laser-abkant-finisaje'],
            ['FINISARE', 'Finisare (zincare, vopsire)', 'Finisare', 'laser-abkant-finisaje'], ['AMBALARE', 'Ambalare', 'Ambalare', 'laser-abkant-finisaje'],
            ['FREZARE', 'Frezare', 'Prelucrări prin așchiere', 'frezare-genos-m460'], ['STRUNJIRE', 'Strunjire', 'Prelucrări prin așchiere', 'strunjire-genos-l3000'],
            ['EROZIUNE-FIR', 'Electroeroziune cu fir', 'Electroeroziune', 'electroeroziune-cut-e600'], ['RECTIFICARE', 'Rectificare plană', 'Rectificare', 'rectificare-okamoto'],
            ['STANTARE', 'Ștanțare', 'Deformare', null], ['GAURIRE', 'Găurire', 'Prelucrări prin așchiere', null], ['MONTAJ', 'Montaj', 'Montaj', null], ['TRANSPORT', 'Transport', 'Logistică', null],
        ];
        foreach ($operations as $i => [$code, $name, $category, $model]) {
            Operation::firstOrCreate(['code' => $code], ['name' => $name, 'category' => $category, 'calc_model_key' => $model, 'sort' => $i + 1]);
        }
    }
}
