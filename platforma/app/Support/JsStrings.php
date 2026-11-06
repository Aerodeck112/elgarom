<?php

namespace App\Support;

/** Strings used by the public JavaScript, translated server-side for the current locale. */
class JsStrings
{
    public const KEYS = [
        'Am precompletat câmpurile cu informațiile din conversația cu asistentul AI. Verificați-le înainte de trimitere.',
        'Puteți atașa cel mult :n fișiere.', ':name: tipul fișierului nu este acceptat.', ':name depășește :mb MB.',
        'Elimină', 'Elimină :name', 'Fișiere', 'Tipul pieselor', 'Descriere', 'Se trimite…', 'Închide', 'Harta – locația Elgarom',
        'Tip piese', 'Material', 'Execuție', 'Cantitate', 'Greutate', 'Dimensiuni', 'Termen',
        'Propunere pentru formularul de ofertă', 'Completează formularul cu aceste date',
        'Nimic nu se trimite până nu verificați și trimiteți formularul. Datele de contact le completați dumneavoastră.',
        'Asistentul scrie…', 'Asistentul nu poate răspunde acum.', 'Mai puteți trimite :n mesaje în această conversație.',
        'Conexiunea a eșuat. Puteți folosi formularul de ofertă.', 'Formular de ofertă', 'Conversația a fost ștearsă.',
    ];

    public static function all(): array
    {
        $t = [];
        foreach (self::KEYS as $k) {
            $t[$k] = __($k);
        }

        return ['locale' => app()->getLocale(), 't' => $t, 'urls' => ['quote' => page_url('solicita-oferta')]];
    }
}
