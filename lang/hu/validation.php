<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'A(z) :attribute mezőt el kell fogadni.',
    'accepted_if' => 'A(z) :attribute mezőt el kell fogadni, ha a(z) :other :value.',
    'active_url' => 'A(z) :attribute mezőnek érvényes URL-nek kell lennie.',
    'after' => 'A(z) :attribute mezőnek dátumnak kell lennie :date után.',
    'after_or_equal' => 'A(z) :attribute mezőnek dátumnak kell lennie vagy egyenlőnek :date.',
    'alpha' => 'A(z) :attribute mező csak betűket tartalmazhat.',
    'alpha_dash' => 'A(z) :attribute mező csak betűket, számokat, kötőjeleket és aláhúzásokat tartalmazhat.',
    'alpha_num' => 'A(z) :attribute mező csak betűket és számokat tartalmazhat.',
    'array' => 'A(z) :attribute mező egy tömbnek kell lennie.',
    'ascii' => 'A(z) :attribute mező csak egy bájtos alfanumerikus karaktereket és szimbólumokat tartalmazhat.',
    'before' => 'A(z) :attribute mezőnek dátumnak kell lennie :date előtt.',
    'before_or_equal' => 'A(z) :attribute mezőnek dátumnak kell lennie vagy egyenlőnek :date.',
    'between' => [
        'array' => 'A(z) :attribute mezőnek :min és :max közötti elemmel kell rendelkeznie.',
        'file' => 'A(z) :attribute mező mérete :min és :max kilobájt között kell legyen.',
        'numeric' => 'A(z) :attribute mezőnek :min és :max közötti értékkel kell rendelkeznie.',
        'string' => 'A(z) :attribute mezőnek :min és :max karakter között kell lennie.',
    ],
    'boolean' => 'A(z) :attribute mező csak igaz vagy hamis lehet.',
    'can' => 'A(z) :attribute mező érvénytelen értéket tartalmaz.',
    'confirmed' => 'A(z) :attribute mező megerősítése nem egyezik.',
    'current_password' => 'A jelszó helytelen.',
    'date' => 'A(z) :attribute mezőnek érvényes dátumnak kell lennie.',
    'date_equals' => 'A(z) :attribute mezőnek a következő dátumnak kell lennie: :date.',
    'date_format' => 'A(z) :attribute mezőnek meg kell egyeznie a következő formátummal: :format.',
    'decimal' => 'A(z) :attribute mezőnek :decimal tizedesjeggyel kell rendelkeznie.',
    'declined' => 'A(z) :attribute mezőt el kell utasítani.',
    'declined_if' => 'A(z) :attribute mezőt el kell utasítani, ha a(z) :other :value.',
    'different' => 'A(z) :attribute és a(z) :other mezőknek különbözőnek kell lenniük.',
    'digits' => 'A(z) :attribute mezőnek :digits számnak kell lennie.',
    'digits_between' => 'A(z) :attribute mezőnek :min és :max számjegy között kell lennie.',
    'dimensions' => 'A(z) :attribute mezőnek érvénytelen képméretekkel kell rendelkeznie.',
    'distinct' => 'A(z) :attribute mezőnek egyedi értékkel kell rendelkeznie.',
    'doesnt_end_with' => 'A(z) :attribute mező nem végződhet az alábbiakkal: :values.',
    'doesnt_start_with' => 'A(z) :attribute mező nem kezdődhet az alábbiakkal: :values.',
    'email' => 'A(z) :attribute mezőnek érvényes email címnek kell lennie.',
    'ends_with' => 'A(z) :attribute mezőnek a következőkkel kell végződnie: :values.',
    'enum' => 'A kiválasztott :attribute érvénytelen.',
    'exists' => 'A kiválasztott :attribute érvénytelen.',
    'extensions' => 'A(z) :attribute mezőnek az alábbi kiterjesztésekkel kell rendelkeznie: :values.',
    'file' => 'A(z) :attribute mezőnek fájlnak kell lennie.',
    'filled' => 'A(z) :attribute mezőnek értékkel kell rendelkeznie.',
    'gt' => [
        'array' => 'A(z) :attribute mezőnek több mint :value elemmel kell rendelkeznie.',
        'file' => 'A(z) :attribute mezőnek nagyobbnak kell lennie, mint :value kilobájt.',
        'numeric' => 'A(z) :attribute mezőnek nagyobbnak kell lennie, mint :value.',
        'string' => 'A(z) :attribute mezőnek több karakterrel kell rendelkeznie, mint :value.',
    ],
    'gte' => [
        'array' => 'A(z) :attribute mezőnek legalább :value elemmel kell rendelkeznie.',
        'file' => 'A(z) :attribute mezőnek legalább :value kilobájt kell lennie.',
        'numeric' => 'A(z) :attribute mezőnek legalább :value-nek kell lennie.',
        'string' => 'A(z) :attribute mezőnek legalább :value karakterrel kell rendelkeznie.',
    ],
    'hex_color' => 'A(z) :attribute mezőnek érvényes hexadecimális színnel kell rendelkeznie.',
    'image' => 'A(z) :attribute mezőnek képnek kell lennie.',
    'in' => 'A kiválasztott :attribute érvénytelen.',
    'in_array' => 'A(z) :attribute mezőnek léteznie kell a(z) :other mezőben.',
    'integer' => 'A(z) :attribute mezőnek egész számnak kell lennie.',
    'ip' => 'A(z) :attribute mezőnek érvényes IP címnek kell lennie.',
    'ipv4' => 'A(z) :attribute mezőnek érvényes IPv4 címnek kell lennie.',
    'ipv6' => 'A(z) :attribute mezőnek érvényes IPv6 címnek kell lennie.',
    'json' => 'A(z) :attribute mezőnek érvényes JSON szövegnek kell lennie.',
    'lowercase' => 'A(z) :attribute mezőnek kisbetűket kell tartalmaznia.',
    'lt' => [
        'array' => 'A(z) :attribute mezőnek kevesebb mint :value elemmel kell rendelkeznie.',
        'file' => 'A(z) :attribute mezőnek kisebbnek kell lennie, mint :value kilobájt.',
        'numeric' => 'A(z) :attribute mezőnek kisebbnek kell lennie, mint :value.',
        'string' => 'A(z) :attribute mezőnek kevesebb karakterrel kell rendelkeznie, mint :value.',
    ],
    'lte' => [
        'array' => 'A(z) :attribute mezőnek nem lehet több, mint :value elem.',
        'file' => 'A(z) :attribute mezőnek nem lehet nagyobb, mint :value kilobájt.',
        'numeric' => 'A(z) :attribute mezőnek nem lehet nagyobb, mint :value.',
        'string' => 'A(z) :attribute mezőnek nem lehet több karaktere, mint :value.',
    ],
    'mac_address' => 'A(z) :attribute mezőnek érvényes MAC címnek kell lennie.',
    'max' => [
        'array' => 'A(z) :attribute mező nem tartalmazhat :max elemet.',
        'file' => 'A(z) :attribute mező mérete nem lehet nagyobb, mint :max kilobájt.',
        'numeric' => 'A(z) :attribute mező értéke nem lehet nagyobb, mint :max.',
        'string' => 'A(z) :attribute mező hossza nem lehet hosszabb, mint :max karakter.',
    ],
    'max_digits' => 'A(z) :attribute mező értéke nem lehet több, mint :max számjegy.',
    'mimes' => 'A(z) :attribute mezőnek a következő típusú fájlnak kell lennie: :values.',
    'mimetypes' => 'A(z) :attribute mezőnek a következő típusú fájlnak kell lennie: :values.',
    'min' => [
        'array' => 'A(z) :attribute mezőnek legalább :min elemmel kell rendelkeznie.',
        'file' => 'A(z) :attribute mező mérete legalább :min kilobájt kell legyen.',
        'numeric' => 'A(z) :attribute mező értéke nem lehet kisebb, mint :min.',
        'string' => 'A(z) :attribute mező hossza nem lehet rövidebb, mint :min karakter.',
    ],
    'min_digits' => 'A(z) :attribute mező értéke nem lehet kevesebb, mint :min számjegy.',
    'missing' => 'A(z) :attribute mező hiányzik.',
    'missing_if' => 'A(z) :attribute mező hiányzik, ha a(z) :other értéke :value.',
    'missing_unless' => 'A(z) :attribute mező hiányzik, hacsak a(z) :other értéke :values.',
    'missing_with' => 'A(z) :attribute mező hiányzik, ha a(z) :values jelen van.',
    'missing_with_all' => 'A(z) :attribute mező hiányzik, ha a(z) :values jelen van.',
    'multiple_of' => 'A(z) :attribute mezőnek a következő többszörösének kell lennie: :value.',
    'not_in' => 'A választott :attribute érvénytelen.',
    'not_regex' => 'A(z) :attribute mező formátuma érvénytelen.',
    'numeric' => 'A(z) :attribute mezőnek számnak kell lennie.',
    'password' => [
        'letters' => 'A(z) :attribute mező legalább egy betűt tartalmaznia kell.',
        'mixed' => 'A(z) :attribute mezőnek legalább egy nagybetűt és egy kisbetűt kell tartalmaznia.',
        'numbers' => 'A(z) :attribute mezőnek legalább egy számot kell tartalmaznia.',
        'symbols' => 'A(z) :attribute mezőnek legalább egy szimbólumot kell tartalmaznia.',
        'uncompromised' => 'A megadott :attribute adatai egy adatvesztésben megjelentek. Kérjük, válasszon másik :attribute-t.',
    ],
    'present' => 'A(z) :attribute mezőnek jelen kell lennie.',
    'present_if' => 'A(z) :attribute mezőnek jelen kell lennie, ha a(z) :other értéke :value.',
    'present_unless' => 'A(z) :attribute mezőnek jelen kell lennie, hacsak a(z) :other értéke :values.',
    'present_with' => 'A(z) :attribute mezőnek jelen kell lennie, ha a(z) :values jelen van.',
    'present_with_all' => 'A(z) :attribute mezőnek jelen kell lennie, ha a(z) :values jelen van.',
    'prohibited' => 'A(z) :attribute mező tiltott.',
    'prohibited_if' => 'A(z) :attribute mező tiltott, ha a(z) :other értéke :value.',
    'prohibited_unless' => 'A(z) :attribute mező tiltott, hacsak a(z) :other értéke :values.',
    'prohibits' => 'A(z) :attribute mező tiltja a(z) :other jelenlétét.',
    'regex' => 'A(z) :attribute mező formátuma érvénytelen.',
    'required' => 'A(z) :attribute mező kitöltése kötelező.',
    'required_array_keys' => 'A(z) :attribute mezőnek tartalmaznia kell a következő kulcsokat: :values.',
    'required_if' => 'A(z) :attribute mező kitöltése kötelező, ha a(z) :other értéke :value.',
    'required_if_accepted' => 'A(z) :attribute mező kitöltése kötelező, ha a(z) :other elfogadásra került.',
    'required_unless' => 'A(z) :attribute mező kitöltése kötelező, hacsak a(z) :other értéke :values.',
    'required_with' => 'A(z) :attribute mező kitöltése kötelező, ha a(z) :values jelen van.',
    'required_with_all' => 'A(z) :attribute mező kitöltése kötelező, ha a(z) :values jelen van.',
    'required_without' => 'A(z) :attribute mező kitöltése kötelező, ha a(z) :values nem jelen van.',
    'required_without_all' => 'A(z) :attribute mező kitöltése kötelező, ha egyik :values sem jelenik meg.',
    'same' => 'A(z) :attribute és :other mezőknek egyezniük kell.',
    'size' => [
        'array' => 'A(z) :attribute mezőnek :size elemet kell tartalmaznia.',
        'file' => 'A(z) :attribute mező mérete :size kilobájt kell legyen.',
        'numeric' => 'A(z) :attribute mező értéke :size kell legyen.',
        'string' => 'A(z) :attribute mező hossza :size karakter kell legyen.',
    ],
    'starts_with' => 'A(z) :attribute mezőnek az alábbiakkal kell kezdődnie: :values.',
    'string' => 'A(z) :attribute mezőnek szövegnek kell lennie.',
    'timezone' => 'A(z) :attribute mezőnek érvényes időzónának kell lennie.',
    'unique' => 'A(z) :attribute már foglalt.',
    'uploaded' => 'A(z) :attribute feltöltése sikertelen.',
    'uppercase' => 'A(z) :attribute mezőnek nagybetűket kell tartalmaznia.',
    'url' => 'A(z) :attribute mezőnek érvényes URL-nek kell lennie.',
    'ulid' => 'A(z) :attribute mezőnek érvényes ULID-nek kell lennie.',
    'uuid' => 'A(z) :attribute mezőnek érvényes UUID-nek kell lennie.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'egyedi-üzenet',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'first_name' => 'Keresztnév',
        'last_name' => 'Vezetéknév',
        'name_hu' => 'Név (hu)',
        'name_en' => 'Név (en)',
        'description_hu' => 'Leírás (hu)',
        'description_en' => 'Leírás (en)',
    ],

];
