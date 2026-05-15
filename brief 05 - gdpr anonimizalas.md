# Brief 05 - GDPR anonimizálás

## Cél

A modul célja, hogy a vendég saját fiókjából GDPR szerinti anonimizálást tudjon kezdeményezni, de csak akkor, ha nincs nyitott tartozása, aktív asztaltagsága vagy nyitott rendelési állapota.

A modul után:
- vendég le tudja kérdezni, hogy indítható-e anonimizálás,
- vendég saját fiók anonimizálását tudja kérni,
- anonimizált vendég személyes adatai maszkoltak vagy töröltek,
- anonimizált fiókkal nem lehet újra belépni,
- rendelési és nyugta előzmények üzleti okból megmaradnak,
- aktív asztal, tagság, nyitott rendelés vagy fizetetlen tétel mellett a művelet tiltott.

## Risk Tier

Tier 2/3.

Indok:
- személyes adatot érint,
- a művelet üzletileg visszafordíthatatlan,
- autentikációs viselkedést módosít,
- rendelés, fizetés és asztaltagság előfeltételeire támaszkodik,
- audit és adatmegőrzési szabályok miatt körültekintést igényel.

## Kiinduló állapot

Jelenleg:
- `guests` tábla soft delete-et használ,
- a vendég saját profilját lekérheti és részben módosíthatja,
- nincs vendégoldali anonimizálási endpoint,
- nincs külön `anonymized_at` mező,
- a `guests.data` JSON mező létezik, használható lehet anonimizálási metaadatokhoz, ha nem akarunk új migrációt,
- rendelési és nyugta kapcsolatok `guest_id` alapján üzletileg megmaradnak.

## Döntési kapu

Implementáció előtt rögzítendő:
- új migrációval legyen-e explicit `anonymized_at` mező, vagy első körben a meglévő `guests.data` JSON mezőben tároljuk,
- kell-e e-mail/confirm token a művelethez az első implementációban,
- a maszkolt e-mail megőrizze-e a domain részt,
- az anonimizált fiók soft delete-et is kapjon-e, vagy `active=false` és maszkolt adatok mellett maradjon technikailag létező rekord,
- staff/admin láthat-e anonimizált vendéget listában, és milyen mezőkkel.

Javasolt első körös döntés:
- legyen új migration `add_anonymization_fields_to_guests`,
- mezők: `anonymized_at` nullable datetime, `anonymization_reason` nullable string vagy `data->anonymization`,
- az anonimizálás állítsa `active=false` értékre,
- `email_verified_at=null`,
- a jelszó véletlenszerű, nem ismert hashre cserélődjön,
- ne legyen belépés anonimizált fiókkal,
- e-mail confirm token külön későbbi keményítés lehet, ha a frontend és levelezési UX is kész.

## Előfeltétel szabályok

Anonimizálás tiltott, ha:
- van nyitott table session, ahol a vendég owner,
- van approved vagy pending table membership nyitott table sessionhöz,
- van fizetetlen rendelési tétel a vendég rendelései között,
- van aktív rendelés `open`, `preparing`, `ready` státuszban,
- a vendég fiók már anonimizált.

Anonimizálás engedhető, ha:
- nincs nyitott asztal vagy tagság,
- nincs pending/fizetetlen rendelési tétel,
- nincs aktív rendelési folyamat,
- korábbi fizetett rendelések és nyugták csak üzleti előzményként maradnak.

## Adatkezelési terv

Anonimizálandó vagy maszkolandó mezők:
- `first_name`,
- `middle_name`,
- `last_name`,
- `email`,
- `picture`,
- `password`,
- `remember_token`, ha létezik,
- `email_verified_at`.

Javasolt értékek:
- `first_name`: `Anonimizált`,
- `middle_name`: `null`,
- `last_name`: `Vendég`,
- `email`: `deleted-guest-{id}@anonymized.local` vagy maszkolt eredeti forma, ha üzletileg szükséges,
- `picture`: `null`,
- `active`: `false`,
- `email_verified_at`: `null`,
- `anonymized_at`: aktuális időpont.

PII-t ne mentsünk vissza audit XML-be vagy JSON-be. Auditban legfeljebb:
- vendég id,
- művelet ideje,
- maszkolt e-mail,
- technikai ok/státusz,
- blokkoló feltételek száma.

## API terv

### Anonimizálási előfeltétel ellenőrzés

`GET /api/guest/me/anonymize/check`

Sikeres response:

```json
{
  "can_anonymize": false,
  "blocking_reasons": [
    {
      "code": "pending_payment",
      "message": "Van fizetésre váró rendelési tétel."
    }
  ]
}
```

Szabály:
- csak bejelentkezett vendég,
- nem módosít adatot,
- frontend ezzel tudja engedélyezni/tiltani az anonimizálási gombot.

### Saját fiók anonimizálása

`POST /api/guest/me/anonymize`

Request első körben:

```json
{
  "confirm": true
}
```

Sikeres response:

```json
{
  "message": "A fiók anonimizálva lett."
}
```

Szabály:
- csak saját fiókra,
- `confirm=true` kötelező,
- blokkoló feltétel esetén `409`,
- siker után a token érvénytelenné váljon vagy a frontend logoutolja a felhasználót.

## OpenAPI terv

Új path dokumentáció:
- `GET /api/guest/me/anonymize/check`,
- `POST /api/guest/me/anonymize`.

Új sémák:
- `GuestAnonymizeCheckResponse`,
- `GuestAnonymizeRequest`,
- `GuestAnonymizeResponse`,
- `GuestAnonymizeBlockingReason`.

Frissítendő sémák:
- `Guest`, ha új `anonymized_at` mező kerül public/staff response-ba.

## Tesztterv

Feature teszt javaslat:
- `GuestAnonymizationTest`.

Happy path:
- vendég lekéri az anonimizálási checket blokkoló feltétel nélkül,
- vendég anonimizálja saját fiókját,
- személyes adatok maszkolódnak,
- `active=false`,
- `anonymized_at` kitöltődik,
- anonimizált vendég nem tud belépni,
- rendelés és nyugta előzmények megmaradnak.

Pesszimista esetek:
- tartozás mellett `409`,
- nyitott table owner mellett `409`,
- approved vagy pending table member mellett `409`,
- aktív rendelés mellett `409`,
- `confirm` hiánya vagy false értéke `422`,
- már anonimizált fiók ismételt anonimizálása `409`,
- más vendég fiókja nem érhető el ezzel az endpointtal.

## Frontend/API TODO

Frontend oldalon követendő:
- profil oldalon anonimizálási állapot/check lekérése,
- blokkoló okok megjelenítése,
- megerősítő modal,
- siker után kijelentkeztetés és visszanavigálás publikus oldalra,
- anonimizált fiók visszaállítását nem szabad ígérni.

## Nyitott, de implementációt nem blokkoló későbbi döntések

- E-mail/confirm token alapú extra megerősítés.
- Külön GDPR audit tábla vagy meglévő `guests.data` használata.
- Staff/admin felületen anonimizált vendégek megjelenítési szabályai.
- Inaktivált, de nem anonimizált fiók adminisztrátori visszaállítási folyamata.
