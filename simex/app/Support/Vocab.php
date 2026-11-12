<?php

namespace App\Support;

/** Fixed vocabularies (key => label) and the badge tone of each status. */
final class Vocab
{
    public const COMPANY_STAGES = [
        'lead' => 'Lead', 'calificare' => 'Calificare', 'cerere' => 'Cerere de ofertă', 'calcul' => 'Calcul tehnic', 'aprobare' => 'Aprobare internă',
        'trimisa' => 'Ofertă trimisă', 'negociere' => 'Negociere', 'client' => 'Client (ofertă acceptată)', 'pierdut' => 'Respins / pierdut',
    ];

    public const COMPANY_KINDS = ['client' => 'Client', 'prospect' => 'Prospect', 'partener' => 'Partener'];

    public const LEAD_SOURCES = ['website' => 'Website', 'recomandare' => 'Recomandare', 'targ' => 'Târg / eveniment', 'email' => 'Email primit', 'telefon' => 'Telefon', 'prospectare' => 'Prospectare', 'client_existent' => 'Client existent', 'altul' => 'Altă sursă'];

    public const LAWFUL_BASES = ['contract' => 'Contract / demersuri precontractuale', 'interes_legitim' => 'Interes legitim (B2B)', 'consimtamant' => 'Consimțământ', 'nedefinit' => 'Nedefinit – fără comunicări comerciale'];

    public const SERVICES = ['piese' => 'Piese și componente metalice', 'stante' => 'Ștanțe, matrițe și scule', 'zincare' => 'Zincare electrolitică'];

    public const RFQ_STATUSES = [
        'noua' => 'Nouă', 'calificare' => 'În calificare', 'calcul' => 'Calcul tehnic', 'aprobare' => 'Aprobare internă', 'ofertata' => 'Ofertată',
        'castigata' => 'Câștigată', 'pierduta' => 'Pierdută', 'anulata' => 'Anulată',
    ];

    public const PRIORITIES = ['scazuta' => 'Scăzută', 'normala' => 'Normală', 'ridicata' => 'Ridicată', 'urgenta' => 'Urgentă'];

    public const QUOTE_STATUSES = [
        'ciorna' => 'Ciornă', 'aprobare' => 'În aprobare', 'aprobata' => 'Aprobată', 'trimisa' => 'Trimisă', 'negociere' => 'Negociere',
        'acceptata' => 'Acceptată', 'respinsa' => 'Respinsă', 'expirata' => 'Expirată', 'anulata' => 'Anulată',
    ];

    public const QUOTE_TYPES = ['piese' => 'Piese metalice', 'stante' => 'Ștanțe și matrițe', 'zincare' => 'Zincare electrolitică', 'mixta' => 'Mixtă'];

    public const ITEM_KINDS = ['piesa' => 'Piesă', 'scula' => 'Ștanță / matriță / sculă', 'zincare' => 'Zincare', 'serviciu' => 'Serviciu', 'logistica' => 'Ambalare / transport', 'nerecurent' => 'Cost nerecurent'];

    public const ACTIVITY_TYPES = ['nota' => 'Notă', 'apel' => 'Apel', 'email' => 'Email', 'intalnire' => 'Întâlnire', 'sarcina' => 'Sarcină', 'sistem' => 'Sistem'];

    public const MATERIAL_CATEGORIES = ['otel' => 'Oțel', 'inox' => 'Inox', 'aluminiu' => 'Aluminiu', 'cupru' => 'Cupru / alamă', 'altul' => 'Material personalizat'];

    public const COST_BASES = ['ora' => 'Pe oră', 'bucata' => 'Pe bucată', 'kg' => 'Pe kilogram', 'm2' => 'Pe m²', 'lot' => 'Pe lot'];

    public const TOOL_TYPES = ['stanta_simpla' => 'Ștanță simplă', 'stanta_progresiva' => 'Ștanță progresivă', 'matrita' => 'Matriță industrială', 'scula_speciala' => 'Sculă specială', 'dispozitiv' => 'Dispozitiv / proiect industrial'];

    public const TOOL_PHASES = ['proiectare' => 'Proiectare', 'realizare' => 'Realizare', 'validare' => 'Validare', 'mentenanta' => 'Mentenanță'];

    public const TOOL_CATEGORIES = [
        'proiectare' => 'Proiectare și inginerie', 'cam' => 'Programare CAM/CNC', 'frezare' => 'Frezare', 'strunjire' => 'Strunjire', 'eroziune_fir' => 'Electroeroziune cu fir',
        'eroziune_electrod' => 'Electroeroziune cu electrod', 'rectificare' => 'Rectificare', 'tratament' => 'Tratamente termice', 'montaj' => 'Montaj', 'ajustare' => 'Ajustare',
        'testare' => 'Testare', 'materiale' => 'Materiale', 'componente' => 'Componente standard achiziționate', 'subcontractare' => 'Subcontractare', 'altele' => 'Costuri suplimentare',
    ];

    public const COMPLEXITIES = ['simpla' => 'Simplă', 'medie' => 'Medie', 'ridicata' => 'Ridicată', 'critica' => 'Critică'];

    public const DOCUMENT_CATEGORIES = ['desen' => 'Desen tehnic', 'model3d' => 'Model 3D', 'specificatie' => 'Specificație', 'oferta' => 'Ofertă', 'contract' => 'Contract', 'foto' => 'Fotografie', 'altele' => 'Altele'];

    public const INCOTERMS = ['EXW', 'FCA', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP', 'FOB', 'CIF'];

    private const TONES = [
        'lead' => 'info', 'calificare' => 'info', 'cerere' => 'info', 'calcul' => 'warn', 'aprobare' => 'warn', 'trimisa' => 'accent', 'negociere' => 'accent',
        'client' => 'ok', 'pierdut' => 'err', 'noua' => 'info', 'ofertata' => 'accent', 'castigata' => 'ok', 'pierduta' => 'err', 'anulata' => 'mute',
        'ciorna' => 'mute', 'aprobata' => 'ok', 'acceptata' => 'ok', 'respinsa' => 'err', 'expirata' => 'mute',
        'ok' => 'ok', 'warn' => 'warn', 'block' => 'err', 'activ' => 'ok', 'validat' => 'info', 'respins' => 'err', 'retras' => 'mute',
        'urgenta' => 'err', 'ridicata' => 'warn', 'normala' => 'mute', 'scazuta' => 'mute',
    ];

    public static function tone(?string $key): string
    {
        return self::TONES[$key] ?? 'mute';
    }

    /** European countries offered in forms (ISO 3166-1 alpha-2 => Romanian name). */
    public static function countries(): array
    {
        return [
            'RO' => 'România', 'AT' => 'Austria', 'BE' => 'Belgia', 'BG' => 'Bulgaria', 'CH' => 'Elveția', 'CZ' => 'Cehia', 'DE' => 'Germania', 'DK' => 'Danemarca',
            'ES' => 'Spania', 'FI' => 'Finlanda', 'FR' => 'Franța', 'GB' => 'Marea Britanie', 'GR' => 'Grecia', 'HR' => 'Croația', 'HU' => 'Ungaria', 'IE' => 'Irlanda',
            'IT' => 'Italia', 'LU' => 'Luxemburg', 'MD' => 'Republica Moldova', 'NL' => 'Țările de Jos', 'NO' => 'Norvegia', 'PL' => 'Polonia', 'PT' => 'Portugalia',
            'RS' => 'Serbia', 'SE' => 'Suedia', 'SI' => 'Slovenia', 'SK' => 'Slovacia', 'TR' => 'Turcia', 'UA' => 'Ucraina',
        ];
    }
}
