<?php

namespace Database\Seeders;

use App\Models\Capability;
use App\Models\Faq;
use App\Models\Industry;
use App\Models\KnowledgeEntry;
use App\Models\Media;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\Section;
use App\Models\Service;
use App\Models\Setting;
use App\Services\MediaService;
use Illuminate\Database\Seeder;

/**
 * Content migrated from the old elgarom.ro (WordPress, copied 2026-11-06), rewritten for clarity.
 * Only facts present on the old site are published. Unknown values are stored unconfirmed
 * (is_confirmed = false / is_published = false) and listed in the admin "De completat" checklist.
 */
class ContentSeeder extends Seeder
{
    private const SRC = 'elgarom.ro (site anterior), preluat 06.11.2026';

    private array $m = [];

    public function run(): void
    {
        if (Page::exists()) {
            $this->command?->warn('Conținutul există deja – seeder-ul nu suprascrie date.');

            return;
        }
        $this->media();
        $this->settings();
        $this->capabilities();
        $this->services();
        $this->faqs();
        $this->industries();
        $this->pages();
        $this->menus();
        $this->redirects();
        $this->knowledge();
    }

    private function media(): void
    {
        $svc = app(MediaService::class);
        $items = [
            'linia-zincare-bai' => ['Linia de zincare electrolitică Elgarom – băile de proces și pasarela de deservire', 'linie'],
            'linia-zincare-pod-transportor' => ['Podul transportor automat deasupra băilor liniei de zincare', 'linie'],
            'linia-zincare-dispozitive' => ['Vedere de sus asupra liniei de zincare, cu dispozitivele de transport', 'linie'],
            'linia-zincare-panorama' => ['Panoramă a băilor liniei de zincare electrolitică', 'linie'],
            'statie-epurare' => ['Stația de epurare și neutralizare a apelor reziduale', 'mediu'],
            'centrifuge-top-coating' => ['Echipamente de centrifugare pentru top coating la piese mici', 'linie'],
            'laborator-chimic' => ['Laboratorul chimic Elgarom', 'laborator'],
            'laborator-analiza' => ['Analiză chimică în laboratorul Elgarom', 'laborator'],
            'hala-receptie-elgarom' => ['Recepția Elgarom, cu vedere spre hala de producție', 'companie'],
            'receptie-elgarom' => ['Intrarea în hala Elgarom', 'companie'],
            'sala-sedinte' => ['Sala de ședințe Elgarom', 'companie'],
            'birouri-coridor' => ['Zona de birouri Elgarom', 'companie'],
            'logo-elgarom' => ['Elgarom', 'identitate'],
            'logo-elgarom-alb' => ['Elgarom', 'identitate'],
        ];
        foreach ($items as $file => [$alt, $folder]) {
            $path = database_path('seed-media/'.$file.(str_starts_with($file, 'logo') ? '.png' : '.jpg'));
            if (! is_file($path)) {
                continue;
            }
            $tmp = tempnam(sys_get_temp_dir(), 'seed');
            copy($path, $tmp);
            $this->m[$file] = $svc->storeImage($tmp, basename($path), $folder, $alt)->id;
            @unlink($tmp);
        }
    }

    private function mid(string $key): ?int
    {
        return $this->m[$key] ?? null;
    }

    private function settings(): void
    {
        $s = [
            'company.name' => 'ELGAROM',
            'company.legal_name' => null,
            'company.cui' => null,
            'company.reg_com' => null,
            'company.tagline' => 'Zincare electrolitică slab acidă, pe dispozitive și în tamburi',
            'company.street' => 'Str. Barajului nr. 3',
            'company.city' => 'Târgu Mureș',
            'company.county' => 'Mureș',
            'company.postal_code' => null,
            'company.country' => 'RO',
            'company.phones' => [
                ['label' => 'Vânzări', 'number' => '0752 144 291'],
                ['label' => 'Tehnic', 'number' => '0748 118 339'],
            ],
            'company.emails' => [
                ['label' => 'Birou', 'email' => 'office@elgarom.ro'],
                ['label' => 'Vânzări', 'email' => 'vanzari@elgarom.ro'],
            ],
            'company.hours' => null,
            'company.map_query' => 'Strada Barajului 3, Târgu Mureș',
            'company.logo_id' => $this->mid('logo-elgarom'),
            'company.logo_dark_id' => $this->mid('logo-elgarom-alb'),
            'group.name' => 'Argento Group',
            'group.url' => 'https://argento-group.com/',
            'footer.text' => 'ELGAROM execută zincare electrolitică slab acidă pe o linie automatizată din Târgu Mureș. Companie parte din Argento Group.',
            'seo.site_name' => 'ELGAROM',
            'seo.default_description' => 'Zincare electrolitică slab acidă în Târgu Mureș: piese pe dispozitive până la 1900 × 1200 × 450 mm și piese mici în tamburi. Solicitați o ofertă.',
            'seo.default_og_image_id' => $this->mid('linia-zincare-bai'),
            'notifications.recipients' => ['vanzari@elgarom.ro'],
            'ai.enabled' => false,
            'ai.model' => 'claude-opus-5-5',
            'ai.effort' => 'low',
            'ai.max_messages_per_session' => 20,
            'ai.max_requests_per_ip_hour' => 30,
            'ai.max_output_tokens' => 4000,
            'ai.daily_budget_usd' => 2,
            'ai.monthly_budget_usd' => 30,
            'ai.retention_days' => 30,
            'cookies.policy_version' => 1,
            'google.mode' => 'direct',
        ];
        foreach ($s as $k => $v) {
            Setting::put($k, $v);
        }
    }

    private function capabilities(): void
    {
        $rows = [
            // Confirmed on the old site ("Tehnologie" page).
            ['linie', 'process', 'Tip proces', 'Zincare electrolitică slab acidă', null, true, true],
            ['linie', 'execution', 'Mod de execuție', 'Pe dispozitive și în tamburi', null, true, true],
            ['dispozitive', 'rack_envelope', 'Gabarit maxim piesă pe dispozitiv', '1900 × 1200 × 450', 'mm', true, true],
            ['tamburi', 'barrel_load', 'Încărcare maximă în tambur', '100', 'kg', true, true],
            ['linie', 'automation', 'Automatizare', 'Linie complet automatizată, parametri monitorizați electronic', null, true, false],
            ['tratamente', 'post', 'Tratamente după zincare', 'Pasivare; top coating (sigilare) pentru piese mici', null, true, false],
            ['laborator', 'lab', 'Control în laborator', 'Analiza băilor active, determinarea grosimii stratului de zinc', null, true, false],
            ['mediu', 'water', 'Ape reziduale', 'Stație automată de epurare și neutralizare', null, true, false],
            // Unknown – stored for completion, never shown publicly while unconfirmed.
            ['strat', 'thickness', 'Grosimi de strat disponibile', null, 'µm', false, false],
            ['strat', 'passivation_types', 'Tipuri de pasivare', null, null, false, false],
            ['strat', 'salt_spray', 'Rezistență în ceață salină (cu metodă de test)', null, 'ore', false, false],
            ['dispozitive', 'rack_weight', 'Greutate maximă pe dispozitiv', null, 'kg', false, false],
            ['tamburi', 'barrel_part_size', 'Dimensiune maximă piesă în tambur', null, 'mm', false, false],
            ['linie', 'tanks', 'Dimensiunile băilor', null, 'mm', false, false],
            ['linie', 'throughput', 'Capacitate de producție', null, null, false, false],
        ];
        foreach ($rows as $i => [$group, $key, $label, $value, $unit, $confirmed, $home]) {
            Capability::create([
                'group' => $group, 'key' => $key, 'label' => $label, 'value' => $value, 'unit' => $unit,
                'is_confirmed' => $confirmed, 'show_on_home' => $home, 'sort' => $i,
                'source' => $confirmed ? self::SRC.', pagina Tehnologie' : null,
                'note' => $confirmed ? null : 'De completat de echipa tehnică. Nu se publică până la confirmare.',
            ]);
        }
    }

    private function services(): void
    {
        $rows = [
            ['zincare-pe-dispozitive', 'Zincare electrolitică pe dispozitive', 'Pentru piese și subansamble mai mari, care se agață individual pe dispozitive. Gabarit maxim: 1900 × 1200 × 450 mm.',
                "Piesele se montează pe dispozitive (agățătoare) și parcurg automat băile liniei. Fiecare piesă este expusă controlat procesului, potrivit pentru piese cu geometrie complexă, suprafețe vizibile sau dimensiuni mari.\n\n- Gabarit maxim pe dispozitiv: **1900 × 1200 × 450 mm**\n- Zincare electrolitică slab acidă, cu agenți de luciu\n- Pasivare pentru creșterea rezistenței la coroziune\n\nTrimiteți-ne desenul sau o fotografie a piesei și vă spunem cum poate fi prinsă pe dispozitiv.", 'linia-zincare-pod-transportor'],
            ['zincare-in-tambur', 'Zincare electrolitică în tamburi', 'Pentru piese mici în vrac – elemente de fixare, piese ștanțate, componente de serie. Până la 100 kg de piese pe încărcare.',
                "Piesele mici se zincă în vrac, în tamburi rotativi care trec automat prin băile liniei. Rotația asigură acoperirea tuturor fețelor pieselor din încărcătură.\n\n- Încărcare maximă: **100 kg** de piese pe tambur\n- Potrivit pentru serii mari de piese mici\n- Opțional: top coating (sigilare) pe linia de centrifuge\n\nPentru o ofertă, indicați tipul piesei, materialul și cantitatea estimată.", 'linia-zincare-dispozitive'],
            ['pasivare-si-top-coating', 'Pasivare și top coating', 'Tratamente aplicate după zincare pentru o rezistență mai bună la coroziune. Sigilarea pieselor mici se face pe o linie dedicată de centrifuge.',
                "După depunerea stratului de zinc, piesele sunt pasivate. Pentru piesele mici zincate și pasivate, Elgarom aplică un tratament suplimentar de sigilare a suprafeței (top coating).\n\nSigilarea se face în patru pași: imersarea pieselor în soluție, scurgerea, centrifugarea și uscarea (polimerizarea) stratului aplicat.\n\nTipul de pasivare și necesitatea top coating-ului se stabilesc împreună cu echipa tehnică, în funcție de cerințele piesei.", 'centrifuge-top-coating'],
            ['consultanta-tehnica-si-laborator', 'Consultanță tehnică și control în laborator', 'Analizăm cerințele piesei înainte de ofertare și verificăm în laborator băile active și grosimea stratului depus.',
                "Fiecare piesă are cerințele ei. Echipa tehnică analizează materialul, geometria și condițiile de utilizare și propune procesul potrivit, în dialog cu dumneavoastră.\n\nLaboratorul chimic Elgarom efectuează:\n\n- analize ale concentrației componentelor din băile active ale liniei;\n- determinări ale grosimii stratului de zinc depus;\n- analize chimice și spectrofotometrice ale apelor tratate.\n\nPentru întrebări tehnice: **0748 118 339**.", 'laborator-analiza'],
        ];
        foreach ($rows as $i => [$slug, $title, $summary, $body, $img]) {
            Service::create(['slug' => $slug, 'title' => $title, 'summary' => $summary, 'body' => $body, 'image_id' => $this->mid($img), 'sort' => $i, 'is_published' => true]);
        }
    }

    private function faqs(): void
    {
        $rows = [
            ['proces', 'Ce tip de zincare executați?', 'Zincare electrolitică slab acidă, pe dispozitive (piese agățate) și în tamburi (piese mici în vrac). Zincarea electrolitică este un proces diferit de zincarea termică, prin imersie în zinc topit.'],
            ['capacitati', 'Care sunt dimensiunile maxime ale pieselor?', 'Pe dispozitive putem zinca piese de până la 1900 × 1200 × 450 mm. În tamburi, încărcarea maximă este de 100 kg de piese mici. Pentru piese la limita acestor dimensiuni, trimiteți-ne desenul pentru verificare.'],
            ['materiale', 'Ce materiale puteți zinca?', 'Avem procese pentru oțel carbon cu conținut scăzut, mediu și ridicat de carbon, oțeluri inoxidabile și oțeluri aliate și înalt aliate. Confirmăm procesul potrivit după analiza piesei.'],
            ['proces', 'Ce tratamente aplicați după zincare?', 'Piesele sunt pasivate după zincare. Pentru piesele mici putem aplica și un top coating (sigilare) pe linia de centrifuge, pentru o rezistență mai bună la coroziune.'],
            ['calitate', 'Cum verificați calitatea stratului?', 'Parametrii procesului sunt monitorizați electronic pe linie. În laboratorul propriu analizăm băile active și determinăm grosimea stratului de zinc depus.'],
            ['oferta', 'Ce informații sunt necesare pentru o ofertă?', 'Ne ajută tipul piesei, materialul, cantitatea, dimensiunile și un desen sau o fotografie. Dacă nu cunoașteți toate datele, trimiteți ce aveți – echipa vă contactează pentru detalii.'],
            ['oferta', 'Pot trimite desene tehnice?', 'Da. În formularul de ofertă puteți încărca până la 6 fișiere PDF, imagini (JPG, PNG, WebP) sau fișiere CAD (DXF, DWG, STEP, IGES), de maximum 15 MB fiecare. Fișierele sunt accesibile doar echipei Elgarom.'],
            ['oferta', 'Cât costă și cât durează zincarea?', 'Prețul și termenul depind de piesă, cantitate și cerințe, de aceea nu le putem estima fără o analiză. Trimiteți o cerere de ofertă și echipa vă răspunde cu o propunere concretă.'],
            ['durabilitate', 'Câți ani rezistă piesele zincate?', 'Durata protecției depinde de grosimea stratului, de pasivare și top coating și, mai ales, de mediul în care este folosită piesa. Nu oferim o valoare generală în ani: discutăm cerințele concrete ale aplicației dumneavoastră.'],
            ['mediu', 'Cum tratați apele rezultate din proces?', 'Apele reziduale acido-alcaline sunt tratate într-o stație automată de epurare și neutralizare, astfel încât apa evacuată să corespundă normelor de mediu în vigoare.'],
        ];
        foreach ($rows as $i => [$cat, $q, $a]) {
            Faq::create(['category' => $cat, 'question' => $q, 'answer' => $a, 'sort' => $i, 'is_published' => true]);
        }
    }

    private function industries(): void
    {
        // Examples only – unpublished until the company confirms the industries it serves.
        foreach (['Construcții de mașini și utilaje', 'Industria auto – componente', 'Construcții și elemente de fixare', 'Echipamente electrice', 'Mobilier și feronerie'] as $i => $t) {
            Industry::create(['title' => $t, 'description' => null, 'is_published' => false, 'sort' => $i]);
        }
    }

    private function page(string $slug, string $template, string $title, ?string $nav, ?string $lead, string $metaTitle, string $metaDesc, array $sections, int $sort, bool $publish = true): Page
    {
        $p = Page::create(['slug' => $slug, 'template' => $template, 'title' => $title, 'nav_title' => $nav, 'lead' => $lead,
            'meta_title' => $metaTitle, 'meta_description' => $metaDesc, 'sort' => $sort, 'is_system' => true, 'status' => 'draft']);
        foreach ($sections as $i => [$type, $data]) {
            Section::create(['page_id' => $p->id, 'type' => $type, 'sort' => $i, 'data' => $data, 'is_visible' => true]);
        }
        if ($publish) {
            $p->refresh()->publish(null);
        }

        return $p;
    }

    private function pages(): void
    {
        $quoteCta = ['cta', [
            'heading' => 'Aveți o piesă de zincat?',
            'text' => 'Trimiteți-ne ce știți despre piesă – chiar și o fotografie ajunge pentru început. Echipa tehnică vă răspunde cu o propunere.',
            'button_label' => 'Solicită ofertă', 'button_url' => '/solicita-oferta',
            'secondary_label' => 'Sună la vânzări', 'secondary_url' => 'tel:+40752144291',
        ]];
        $durability = ['durability', [
            'heading' => 'Cât durează protecția',
            'intro' => 'Stratul de zinc protejează oțelul în două feluri: ca barieră între metal și mediu și prin coroziunea zincului în locul oțelului. Cât timp rezistă depinde de câțiva factori concreți.',
            'items' => [
                ['title' => 'Grosimea stratului', 'text' => 'Un strat mai gros se consumă mai încet. Grosimea se stabilește după cerințele piesei și se verifică în laborator.'],
                ['title' => 'Pasivarea', 'text' => 'Pasivarea aplicată după zincare încetinește coroziunea stratului de zinc.'],
                ['title' => 'Top coating', 'text' => 'Sigilarea suplimentară, disponibilă pentru piesele mici, crește rezistența la coroziune.'],
                ['title' => 'Mediul de utilizare', 'text' => 'Umiditatea, sarea, agenții chimici și condensul accelerează coroziunea. Aceeași piesă rezistă diferit în interior și în exterior.'],
            ],
            'note' => 'Graficul este o ilustrație calitativă, nu o garanție. Durata protecției pentru aplicația dumneavoastră o discutăm pe baza cerințelor concrete.',
        ]];

        $this->page('/', 'home', 'Transformarea oțelului. Protecție construită să dureze.', 'Acasă',
            'Zincare electrolitică slab acidă pentru piese din oțel, pe o linie complet automatizată în Târgu Mureș. Piese mari pe dispozitive, piese mici în tamburi.',
            'Zincare electrolitică în Târgu Mureș | ELGAROM',
            'Zincare electrolitică slab acidă pe dispozitive (până la 1900 × 1200 × 450 mm) și în tamburi (până la 100 kg). Linie automatizată, laborator propriu. Solicitați ofertă.',
            [
                ['scene', ['items' => [
                    ['title' => 'Piesa din oțel, pregătită pe dispozitiv', 'text' => 'Piesele mari se agață pe dispozitive; piesele mici se încarcă în tamburi.'],
                    ['title' => 'Coborâre controlată în baie', 'text' => 'Podul transportor automat duce piesele prin băile liniei, fără intervenție manuală.'],
                    ['title' => 'Depunere electrolitică de zinc', 'text' => 'În baia slab acidă, zincul se depune pe suprafața piesei. Parametrii sunt monitorizați electronic.'],
                    ['title' => 'Strat de zinc uniform', 'text' => 'Piesa iese cu un strat de zinc continuu, depus controlat pe toată suprafața.'],
                    ['title' => 'Pasivare pentru protecție mai bună', 'text' => 'Pasivarea și, pentru piese mici, top coating-ul cresc rezistența la coroziune.'],
                ], 'note' => 'Ilustrație simplificată. Procesul real include mai multe băi și etape, descrise în pagina Procesul de zincare.']],
                ['features', ['heading' => 'De ce aleg clienții industriali Elgarom', 'intro' => 'O linie de zincare proiectată și construită împreună cu parteneri europeni cu peste 40 de ani de experiență în domeniu.', 'items' => [
                    ['title' => 'Aceeași calitate pe tot lotul', 'text' => 'Linia este complet automatizată: secvențele de proces sunt coordonate de sistem, iar intervenția manuală este redusă la minimum.'],
                    ['title' => 'Strat depus controlat', 'text' => 'Parametrii tehnologici sunt monitorizați electronic, ca întreaga suprafață a piesei să corespundă cerințelor.'],
                    ['title' => 'Piese mari și piese mici', 'text' => 'Pe dispozitive: piese de până la 1900 × 1200 × 450 mm. În tamburi: până la 100 kg de piese mici pe încărcare.'],
                    ['title' => 'Laborator propriu', 'text' => 'Analizăm băile active și determinăm grosimea stratului de zinc depus.'],
                    ['title' => 'Protecție suplimentară', 'text' => 'Pasivare după zincare și top coating (sigilare) pentru piesele mici, pe o linie dedicată de centrifuge.'],
                    ['title' => 'Ape tratate înainte de evacuare', 'text' => 'O stație automată de epurare neutralizează apele reziduale conform normelor de mediu.'],
                ]]],
                ['envelope', ['heading' => 'Ce încape pe linie', 'intro' => 'Gabaritul maxim al unei piese pe dispozitiv și încărcarea maximă a unui tambur.']],
                ['photo_band', ['image' => $this->mid('linia-zincare-bai'), 'caption' => 'Linia de zincare electrolitică Elgarom, Târgu Mureș']],
                ['services', ['heading' => 'Servicii', 'intro' => 'Zincare pentru piese de serie individuale, mici și mari, din industrii diverse.']],
                $durability,
                ['group', ['heading' => 'Parte din Argento Group', 'body' => 'ELGAROM face parte din Argento Group, alături de companii precum Fepo și Euronarcis.', 'companies' => [
                    ['name' => 'Argento Group', 'url' => 'https://argento-group.com/', 'text' => ''],
                    ['name' => 'Fepo', 'url' => 'https://fepo.ro/', 'text' => ''],
                    ['name' => 'Euronarcis', 'url' => 'https://www.euronarcis.com/', 'text' => ''],
                ]]],
                ['quote_short', ['heading' => 'Scrieți-ne pe scurt', 'intro' => 'Pentru o ofertă detaliată folosiți formularul complet. Pentru o întrebare rapidă, ajunge un mesaj.']],
            ], 0);

        $this->page('despre', 'standard', 'Despre Elgarom', 'Despre', 'Furnizor de servicii de zincare electrolitică, cu o linie automatizată în Târgu Mureș și o echipă tehnică dedicată.',
            'Despre Elgarom și Argento Group | ELGAROM', 'ELGAROM deține o linie automatizată de zincare electrolitică slab acidă în Târgu Mureș și face parte din Argento Group.', [
                ['intro', ['heading' => 'O linie de zincare construită pentru precizie', 'body' => "ELGAROM deține una dintre cele mai performante linii de zincare electrolitică din România, proiectată și construită împreună cu parteneri cu renume la nivel european.\n\nÎntregul proces de zincare este automatizat. Sisteme complexe alcătuiesc, supraveghează și coordonează funcționarea liniei, care poate executa zincare electrolitică slab acidă atât pe dispozitive, cât și în tamburi.", 'image' => $this->mid('hala-receptie-elgarom'), 'image_side' => 'right']],
                ['features', ['heading' => 'Cum lucrăm', 'items' => [
                    ['title' => 'Soluții pentru fiecare piesă', 'text' => 'Dezvoltăm soluții de acoperire pentru produsele clienților, în dialog strâns cu aceștia.'],
                    ['title' => 'Serviciu tehnic', 'text' => 'Echipa de specialiști vă sprijină în toate problemele legate de tratarea suprafeței materialelor.'],
                    ['title' => 'Serii mici și mari', 'text' => 'Acoperim piese de serie individuale, mici și mari, pentru industrii din domenii diverse.'],
                    ['title' => 'Producție responsabilă', 'text' => 'Producția ecologică în toate procesele face parte din filozofia companiei. Apele reziduale sunt epurate automat.'],
                ]]],
                ['text', ['heading' => 'Calitate și inovație', 'body' => "În toate procesele urmărim o calitate și o precizie de finisare constante și reproductibile. Secvențele automate de proces și sistemele de asigurare a calității asigură fiabilitate și o calitate exactă a suprafeței.\n\nGândirea inovatoare și orientarea spre viitor sunt importante pentru noi. Prin aplicarea și dezvoltarea tehnologiei de proces pentru fiecare client, vrem să contribuim durabil la succesul clienților noștri."]],
                ['group', ['heading' => 'Argento Group', 'body' => 'ELGAROM face parte din Argento Group. Din grup fac parte și companiile Fepo și Euronarcis.', 'companies' => [
                    ['name' => 'Argento Group', 'url' => 'https://argento-group.com/', 'text' => ''],
                    ['name' => 'Fepo', 'url' => 'https://fepo.ro/', 'text' => ''],
                    ['name' => 'Euronarcis', 'url' => 'https://www.euronarcis.com/', 'text' => ''],
                ]]],
                ['gallery', ['heading' => 'Elgarom în imagini', 'images' => array_values(array_filter([$this->mid('receptie-elgarom'), $this->mid('linia-zincare-pod-transportor'), $this->mid('laborator-chimic'), $this->mid('statie-epurare'), $this->mid('sala-sedinte'), $this->mid('birouri-coridor')]))]],
                $quoteCta,
            ], 10);

        $this->page('servicii', 'services', 'Servicii de zincare', 'Servicii', 'Zincare electrolitică slab acidă pe dispozitive și în tamburi, urmată de pasivare și, pentru piesele mici, de top coating.',
            'Servicii de zincare electrolitică | ELGAROM', 'Zincare pe dispozitive (până la 1900 × 1200 × 450 mm), zincare în tamburi (până la 100 kg), pasivare și top coating, control în laborator.', [
                ['services', ['heading' => 'Ce executăm', 'intro' => '']],
                ['features', ['heading' => 'Materiale', 'intro' => 'Avem procese adecvate pentru următoarele categorii de oțeluri. Procesul exact îl confirmăm după analiza piesei.', 'items' => [
                    ['title' => 'Oțel carbon cu conținut scăzut de carbon', 'text' => ''],
                    ['title' => 'Oțel carbon cu conținut mediu de carbon', 'text' => ''],
                    ['title' => 'Oțel carbon cu conținut ridicat de carbon', 'text' => ''],
                    ['title' => 'Oțeluri inoxidabile', 'text' => ''],
                    ['title' => 'Oțeluri aliate și înalt aliate', 'text' => ''],
                ]]],
                $quoteCta,
            ], 20);

        $this->page('procesul-de-zincare', 'process', 'Procesul de zincare', 'Proces', 'Cum ajunge o piesă din oțel brut la un strat de zinc pasivat, pe linia automatizată Elgarom.',
            'Procesul de zincare electrolitică | ELGAROM', 'Etapele zincării electrolitice slab acide la Elgarom: analiza piesei, montare pe dispozitiv sau în tambur, zincare, pasivare, top coating și control în laborator.', [
                ['steps', ['heading' => 'Etapele, pe scurt', 'intro' => 'Ilustrația de pe prima pagină arată doar momentul zincării. Pe linie, o piesă trece prin mai multe etape:', 'items' => [
                    ['title' => 'Analiza piesei și a cerințelor', 'text' => 'Echipa tehnică evaluează materialul, geometria și cerințele de protecție și stabilește procesul potrivit.'],
                    ['title' => 'Montare pe dispozitiv sau încărcare în tambur', 'text' => 'Piesele mari se agață pe dispozitive (gabarit maxim 1900 × 1200 × 450 mm). Piesele mici se încarcă în vrac în tamburi (maximum 100 kg pe încărcare).'],
                    ['title' => 'Zincare electrolitică slab acidă', 'text' => 'Podul transportor automat duce piesele prin băile liniei. Parametrii tehnologici sunt monitorizați electronic, pentru o depunere controlată a stratului pe toată suprafața. Băile conțin un sistem de agenți de luciu.'],
                    ['title' => 'Pasivare', 'text' => 'După zincare, piesele sunt tratate cu soluții de pasivare pentru o rezistență mai bună la coroziune.'],
                    ['title' => 'Top coating pentru piese mici', 'text' => 'Piesele mici zincate și pasivate pot fi sigilate: imersare în soluție, scurgere, centrifugare și uscare (polimerizare).'],
                    ['title' => 'Control în laborator', 'text' => 'Laboratorul analizează concentrația componentelor din băile active și determină grosimea stratului de zinc depus.'],
                ], 'note' => 'Băile intermediare și succesiunea exactă se stabilesc pentru fiecare tip de piesă de către echipa tehnică.']],
                ['photo_band', ['image' => $this->mid('linia-zincare-pod-transportor'), 'caption' => 'Podul transportor automat al liniei de zincare']],
                ['intro', ['heading' => 'În paralel: apele reziduale sunt tratate', 'body' => 'Stația de epurare și neutralizare tratează automat apele reziduale acido-alcaline rezultate din proces. Monitorizarea electronică prin calculatorul de proces asigură extragerea elementelor chimice dăunătoare, astfel încât apa evacuată să corespundă normelor de mediu în vigoare. Laboratorul verifică apa tratată prin analize chimice și spectrofotometrice.', 'image' => $this->mid('statie-epurare'), 'image_side' => 'left']],
                $durability,
                $quoteCta,
            ], 30);

        $this->page('capacitati-tehnice', 'capabilities', 'Capacități tehnice', 'Capacități', 'Datele confirmate ale liniei de zincare Elgarom. Pentru alte valori, echipa tehnică vă răspunde direct.',
            'Capacități tehnice – linia de zincare | ELGAROM', 'Gabarit maxim pe dispozitiv 1900 × 1200 × 450 mm, încărcare tambur 100 kg, linie automatizată, laborator propriu, stație de epurare.', [
                ['envelope', ['heading' => 'Gabarit și încărcare', 'intro' => '']],
                ['capabilities', ['heading' => 'Date tehnice confirmate', 'intro' => 'Afișăm doar valorile confirmate. Pentru grosimi de strat, tipuri de pasivare sau alte cerințe, întrebați echipa tehnică.', 'group' => '']],
                ['intro', ['heading' => 'Linia de zincare electrolitică', 'body' => 'Linia a fost proiectată și construită împreună cu parteneri cu renume la nivel european, cu peste 40 de ani de experiență în domeniu. Întregul proces este automatizat; sistemele care alcătuiesc, supraveghează și coordonează funcționarea echipamentelor reduc intervenția umană la minimum.', 'image' => $this->mid('linia-zincare-dispozitive'), 'image_side' => 'right']],
                ['intro', ['heading' => 'Laboratorul chimic', 'body' => "Laboratorul este dotat pentru:\n\n- analize ale concentrației componentelor chimice din băile active;\n- analize chimice și spectrofotometrice ale apelor tratate și evacuate;\n- determinări ale grosimii stratului de zinc depus.", 'image' => $this->mid('laborator-chimic'), 'image_side' => 'left']],
                ['intro', ['heading' => 'Linia de centrifuge pentru top coating', 'body' => 'Piesele mici zincate și pasivate sunt sigilate pe o linie dedicată: imersare în soluția de sigilare, scurgere, centrifugare și uscare (polimerizare).', 'image' => $this->mid('centrifuge-top-coating'), 'image_side' => 'right']],
                $quoteCta,
            ], 40);

        $this->page('aplicatii', 'industries', 'Aplicații și industrii', 'Aplicații', 'Zincăm piese de serie individuale, mici și mari, pentru industrii din domenii diverse.',
            'Aplicații ale zincării electrolitice | ELGAROM', 'Piese mici în serie, piese ștanțate, subansamble și piese mari din oțel – zincate pe dispozitive sau în tamburi la Elgarom.', [
                ['industries', ['heading' => 'Industrii deservite', 'intro' => '']],
                ['features', ['heading' => 'Ce fel de piese', 'intro' => 'Procesul se alege după mărimea și forma piesei.', 'items' => [
                    ['title' => 'Piese mici, în vrac', 'text' => 'Elemente de fixare, piese ștanțate și alte componente mici de serie se zincă în tamburi – până la 100 kg pe încărcare.'],
                    ['title' => 'Piese mari sau cu geometrie complexă', 'text' => 'Subansamble, piese prelucrate și piese cu suprafețe vizibile se zincă pe dispozitive – până la 1900 × 1200 × 450 mm.'],
                ]]],
                $quoteCta,
            ], 50);

        $this->page('proiecte', 'projects', 'Proiecte și galerie', 'Proiecte', 'Instalațiile Elgarom și, cu acordul clienților, proiecte realizate.',
            'Proiecte și galerie foto | ELGAROM', 'Fotografii ale liniei de zincare, laboratorului și stației de epurare Elgarom din Târgu Mureș.', [
                ['projects', ['heading' => 'Proiecte', 'intro' => '']],
                ['gallery', ['heading' => 'Galerie foto', 'intro' => 'Linia de zincare, laboratorul și stația de epurare, în Târgu Mureș.', 'images' => array_values(array_filter([$this->mid('linia-zincare-bai'), $this->mid('linia-zincare-pod-transportor'), $this->mid('linia-zincare-dispozitive'), $this->mid('linia-zincare-panorama'), $this->mid('centrifuge-top-coating'), $this->mid('laborator-chimic'), $this->mid('laborator-analiza'), $this->mid('statie-epurare'), $this->mid('hala-receptie-elgarom')]))]],
                $quoteCta,
            ], 60);

        $this->page('calitate', 'quality', 'Calitate și documente', 'Calitate', 'Proces automatizat, parametri monitorizați și control în laborator.',
            'Calitate și documente | ELGAROM', 'Controlul calității la Elgarom: proces automatizat, monitorizare electronică a parametrilor, laborator chimic propriu.', [
                ['intro', ['heading' => 'Calitate reproductibilă', 'body' => "Secvențele automate de proces și monitorizarea electronică a parametrilor tehnologici asigură o calitate constantă a suprafeței de la un lot la altul.\n\nÎn laboratorul propriu verificăm concentrația băilor active și grosimea stratului de zinc depus.", 'image' => $this->mid('laborator-analiza'), 'image_side' => 'right']],
                ['documents', ['heading' => 'Documente', 'intro' => 'Certificate și documente tehnice disponibile pentru descărcare.', 'category' => '']],
                $quoteCta,
            ], 70);

        $this->page('intrebari-frecvente', 'faq', 'Întrebări frecvente', 'Întrebări', 'Răspunsuri scurte despre zincare, dimensiuni, materiale și ofertare.',
            'Întrebări frecvente despre zincare | ELGAROM', 'Ce tip de zincare execută Elgarom, dimensiuni maxime, materiale, tratamente, calitate și cum solicitați o ofertă.', [
                ['faq', ['heading' => '', 'category' => '']],
                $quoteCta,
            ], 80);

        $this->page('solicita-oferta', 'quote', 'Solicită ofertă', 'Ofertă', 'Spuneți-ne ce piesă aveți. Câmpurile pe care nu le cunoașteți pot rămâne goale.',
            'Solicită ofertă pentru zincare | ELGAROM', 'Cerere de ofertă pentru zincare electrolitică: descrieți piesa, cantitatea și încărcați desene sau fotografii.', [], 90);

        $this->page('contact', 'contact', 'Contact', 'Contact', 'Vânzări, suport tehnic și adresa liniei de zincare din Târgu Mureș.',
            'Contact | ELGAROM Târgu Mureș', 'ELGAROM, Str. Barajului nr. 3, Târgu Mureș. Vânzări 0752 144 291, tehnic 0748 118 339, vanzari@elgarom.ro.', [
                ['contact', ['heading' => 'Date de contact', 'intro' => '']],
                ['quote_short', ['heading' => 'Trimiteți un mesaj', 'intro' => 'Răspundem la mesaje în zilele lucrătoare.']],
            ], 100);

        $this->page('politica-de-confidentialitate', 'legal', 'Politica de confidențialitate', null, null,
            'Politica de confidențialitate | ELGAROM', 'Cum prelucrează ELGAROM datele personale transmise prin site.', [
                ['text', ['heading' => '', 'body' => $this->privacy()]],
            ], 200);

        $this->page('politica-cookies', 'legal', 'Politica de cookies', null, null,
            'Politica de cookies | ELGAROM', 'Ce cookie-uri folosește site-ul ELGAROM și cum vă puteți schimba preferințele.', [
                ['text', ['heading' => '', 'body' => $this->cookies()]],
            ], 210);
    }

    private function privacy(): string
    {
        return <<<'MD'
Această politică explică ce date personale prelucrăm atunci când folosiți site-ul elgarom.ro, de ce și ce drepturi aveți. Operatorul datelor este {{company}}, cu sediul la adresa {{address}}. Ne puteți contacta la {{email}}.

## Ce date prelucrăm

- **Cereri de ofertă și mesaje:** nume, companie, email, telefon, localitate, descrierea pieselor și fișierele încărcate (desene, fotografii).
- **Asistentul AI:** mesajele scrise în conversație, păstrate pentru perioada afișată în fereastra asistentului.
- **Date tehnice:** adresa IP (păstrată sub formă pseudonimizată pentru protecția împotriva abuzurilor), tipul browserului.
- **Statistici și marketing:** numai dacă vă dați acordul în bannerul de cookies.

## De ce le prelucrăm

- pentru a răspunde cererii dumneavoastră și a pregăti o ofertă (demersuri precontractuale, art. 6 alin. (1) lit. b GDPR);
- pentru securitatea site-ului și prevenirea abuzurilor (interes legitim, art. 6 alin. (1) lit. f GDPR);
- pentru informări comerciale, doar dacă ați bifat separat acordul de marketing (consimțământ, art. 6 alin. (1) lit. a GDPR).

## Cât timp le păstrăm

Cererile de ofertă se păstrează cât este necesar pentru ofertare și relația comercială, apoi conform obligațiilor legale. Conversațiile cu asistentul AI se șterg automat după perioada configurată. Vă puteți retrage oricând acordul de marketing.

## Cui le transmitem

Datele sunt accesibile doar personalului autorizat Elgarom. Folosim furnizori pentru găzduire și email. Dacă folosiți asistentul AI, mesajele sunt procesate de furnizorul serviciului AI. Datele de statistică sunt transmise Google doar cu acordul dumneavoastră.

## Drepturile dumneavoastră

Aveți dreptul de acces, rectificare, ștergere, restricționare, portabilitate și opoziție, precum și dreptul de a vă retrage consimțământul. Pentru exercitarea lor scrieți-ne la {{email}}. Puteți depune o plângere la Autoritatea Națională de Supraveghere a Prelucrării Datelor cu Caracter Personal (www.dataprotection.ro).
MD;
    }

    private function cookies(): string
    {
        return <<<'MD'
Site-ul folosește cookie-uri strict necesare pentru funcționare și, numai cu acordul dumneavoastră, cookie-uri de statistică și marketing.

## Categorii

- **Strict necesare:** sesiunea, protecția formularelor (CSRF) și memorarea preferințelor de cookies. Nu pot fi dezactivate.
- **Statistică (Google Analytics):** ne arată cum este folosit site-ul. Se activează numai după acord.
- **Marketing (Google Ads):** măsoară eficiența campaniilor. Se activează numai după acord.

Până la alegerea dumneavoastră nu se încarcă etichete Google de statistică sau marketing. Dacă vă retrageți acordul, cookie-urile Google setate de site sunt șterse.

Harta Google de pe pagina Contact se încarcă doar când apăsați „Afișează harta”.

## Cum vă schimbați preferințele

Folosiți linkul „Preferințe cookies” din subsolul site-ului. Puteți șterge oricând cookie-urile din setările browserului.
MD;
    }

    private function menus(): void
    {
        $header = [
            ['Servicii', '/servicii'], ['Proces', '/procesul-de-zincare'], ['Capacități', '/capacitati-tehnice'],
            ['Aplicații', '/aplicatii'], ['Proiecte', '/proiecte'], ['Despre', '/despre'], ['Contact', '/contact'],
        ];
        foreach ($header as $i => [$l, $u]) {
            MenuItem::create(['menu' => 'header', 'label' => $l, 'url' => $u, 'sort' => $i]);
        }
        MenuItem::create(['menu' => 'header', 'label' => 'Solicită ofertă', 'url' => '/solicita-oferta', 'sort' => 99, 'is_button' => true]);
        $footer = [
            ['Calitate și documente', '/calitate'], ['Întrebări frecvente', '/intrebari-frecvente'], ['Solicită ofertă', '/solicita-oferta'],
            ['Politica de confidențialitate', '/politica-de-confidentialitate'], ['Politica de cookies', '/politica-cookies'],
        ];
        foreach ($footer as $i => [$l, $u]) {
            MenuItem::create(['menu' => 'footer', 'label' => $l, 'url' => $u, 'sort' => $i]);
        }
    }

    private function redirects(): void
    {
        $r = [
            ['/acasa', '/', 301], ['/despre-noi', '/despre', 301], ['/tehnologie', '/procesul-de-zincare', 301],
            ['/wp-sitemap.xml', '/sitemap.xml', 301], ['/wp-sitemap-posts-page-1.xml', '/sitemap.xml', 301],
            ['/feed', '/', 301], ['/comments/feed', '/', 301],
            ['/wp-admin', null, 410], ['/wp-login.php', null, 410], ['/xmlrpc.php', null, 410],
        ];
        foreach ($r as [$from, $to, $code]) {
            Redirect::create(['from_path' => $from, 'to_url' => $to, 'status_code' => $code]);
        }
        // Old WordPress image URLs that may be linked from elsewhere.
        $map = [
            '/wp-content/uploads/2024/06/b0515c42151c24f033cc42778ded0907-scaled.jpg' => 'linia-zincare-bai',
            '/wp-content/uploads/2024/06/bc916fff32e77d7144fb2fe468e886a0-scaled.jpg' => 'linia-zincare-pod-transportor',
            '/wp-content/uploads/2024/06/2f6f71c6d5595f7f3ef215819961aeb0-scaled.jpg' => 'statie-epurare',
            '/wp-content/uploads/2024/06/53f83fec7cb14174c9d4c256a92a42a5-scaled.jpg' => 'laborator-chimic',
            '/wp-content/uploads/2024/06/e69cf76ddbe860f3942fa82c49fb0944-scaled.jpg' => 'laborator-analiza',
            '/wp-content/uploads/2024/06/3e8fe1fc57d8f207e8891e97aa76b6a8-scaled.jpg' => 'centrifuge-top-coating',
            '/wp-content/uploads/2026/01/Elgarom-scaled.jpg' => 'receptie-elgarom',
            '/wp-content/uploads/2023/08/logo-white.png' => 'logo-elgarom',
        ];
        foreach ($map as $from => $key) {
            if ($id = $this->mid($key)) {
                Redirect::create(['from_path' => $from, 'to_url' => '/media/'.Media::find($id)->path, 'status_code' => 301]);
            }
        }
    }

    private function knowledge(): void
    {
        $k = [
            ['Tipul de zincare', 'ELGAROM execută zincare electrolitică slab acidă, pe dispozitive (piese agățate) și în tamburi (piese mici în vrac). Zincarea termică (prin imersie în zinc topit) nu este prezentată ca serviciu Elgarom; pentru alte procese, clientul trebuie îndrumat către echipă.'],
            ['Dimensiuni și încărcare', 'Pe dispozitive, piesele pot avea dimensiuni de până la 1900 × 1200 × 450 mm. Tamburii au o capacitate maximă de încărcare de 100 kg de piese. Alte limite (greutate pe dispozitiv, dimensiunea maximă a unei piese în tambur) nu sunt publicate și trebuie confirmate de echipa tehnică.'],
            ['Linia de zincare', 'Linia a fost proiectată și construită împreună cu parteneri europeni cu peste 40 de ani de experiență în domeniu. Procesul este complet automatizat, iar parametrii tehnologici sunt monitorizați electronic pentru depunerea controlată a stratului de zinc.'],
            ['Chimie și tratamente', 'Soluțiile procesului slab acid conțin agenți de luciu, soluții de pasivare și top coating. Piesele mici zincate și pasivate pot fi sigilate (top coating) pe o linie de centrifuge: imersare, scurgere, centrifugare, uscare (polimerizare).'],
            ['Laborator', 'Laboratorul chimic analizează concentrația componentelor din băile active, face analize chimice și spectrofotometrice ale apelor tratate și determină grosimea stratului de zinc depus.'],
            ['Mediu', 'O stație automată de epurare și neutralizare tratează apele reziduale acido-alcaline, astfel încât apa evacuată să corespundă normelor de mediu în vigoare.'],
            ['Materiale', 'ELGAROM prezintă procese adecvate pentru: oțel carbon cu conținut scăzut, mediu și ridicat de carbon, oțeluri inoxidabile, oțeluri aliate și înalt aliate. Procesul exact se confirmă după analiza piesei.'],
            ['Contact', 'Vânzări: 0752 144 291, vanzari@elgarom.ro. Tehnic: 0748 118 339. Birou: office@elgarom.ro. Adresa: Str. Barajului nr. 3, Târgu Mureș, jud. Mureș. Cererile de ofertă se trimit prin formularul de pe pagina /solicita-oferta.'],
            ['Grup', 'ELGAROM face parte din Argento Group (argento-group.com), alături de Fepo și Euronarcis.'],
            ['Ce nu se știe public', 'Nu sunt publicate: prețuri, termene de execuție, grosimi de strat standard, ore de test în ceață salină, certificări, garanții sau durate de protecție în ani. Pentru acestea, clientul trebuie îndrumat către echipa Elgarom.'],
        ];
        foreach ($k as $i => [$t, $c]) {
            KnowledgeEntry::create(['title' => $t, 'content' => $c, 'source_label' => self::SRC, 'source_url' => 'https://elgarom.ro/tehnologie', 'is_active' => true, 'reviewed_at' => now(), 'sort' => $i]);
        }
    }
}
