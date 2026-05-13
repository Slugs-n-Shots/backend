# Brief 01 - Asztal törzsadat és foglalás

## Cél

Az első modul célja az asztalok backend oldali törzsadat-kezelése és vendég oldali foglalása QR/GUID kóddal.

A modul után:
- admin/személyzet tud asztalt létrehozni, listázni, módosítani, inaktiválni,
- minden asztalnak van nem kitalálható, egyedi GUID kódja,
- minden asztalnak van vendég számára látható neve,
- vendég appból név szerint láthatja a szabad asztalok listáját,
- bejelentkezett vendég GUID alapján lefoglalhat egy szabad és aktív asztalt,
- bejelentkezett vendég a szabad asztalok listájából is indíthat foglalást,
- a foglaláskor létrejön egy nyitott asztalhasználat / table session,
- a foglaló vendég lesz az asztalhasználat felelőse,
- ugyanaz az asztal ugyanazon nyitási napon belül is újra foglalható, ha az előző table session lezárult,
- látogató nem foglalhat asztalt,
- foglalt asztal GUID-ja nem cserélhető.

## Risk Tier

Tier 2.

Indok:
- új perzisztens adatmodell és migration készül,
- jogosultsági szabályt érint,
- vendéghez kötött üzleti állapotot vezet be,
- későbbi rendelés/fizetés folyamat erre fog épülni.

## Érintett táblák

Új tábla:
- `tables`
- `table_sessions`

Közvetlenül nem módosított meglévő táblák:
- `guests`

Kapcsolat:
- `table_sessions.table_id` -> `tables.id`
- `table_sessions.owner_guest_id` -> `guests.id`

## Migration terv

Új migrationök:
- `create_tables_table`
- `create_table_sessions_table`

`tables` mezők:
- `id`: primary key
- `name`: string, kötelező
- `guid`: string(36), kötelező, egyedi, külső QR/foglalási kód
- `active`: boolean, default `true`
- `created_at`, `updated_at`
- `deleted_at` soft delete mező

`table_sessions` mezők:
- `id`: primary key
- `table_id`: foreign id, kötelező
- `owner_guest_id`: foreign id, kötelező
- `business_date`: date, kötelező
- `opened_at`: datetime/timestamp, kötelező
- `closed_at`: datetime/timestamp, nullable
- `status`: string vagy enum jellegű string, értékek: `open`, `closed`
- `created_at`, `updated_at`

Indexek:
- `tables.id`: primary key
- `tables.guid`: unique index
- `table_sessions.id`: primary key
- `table_sessions.table_id`
- `table_sessions.owner_guest_id`
- `table_sessions.status`
- `table_sessions.table_id + status`
- `table_sessions.owner_guest_id + status`

Megjegyzés:
- A `tables` tábla törzsadat, nem a konkrét foglalás/használat állapotának elsődleges forrása.
- A konkrét foglalás/asztalhasználat a `table_sessions` táblába kerül.
- Az asztal akkor foglalt, ha van hozzá `open` státuszú, `closed_at = null` table session.
- Egy asztalhoz egyszerre csak egy nyitott table session tartozhat.
- Egy vendég egyszerre csak egy nyitott table session felelőse lehet.
- Ugyanaz az asztal ugyanazon nyitási napon belül többször is foglalható, ha az előző session lezárult.
- A `business_date` az üzleti/nyitási napot jelöli, nem feltétlenül naptári napot. Első körben számolható az aktuális dátumból, később nyitvatartási szabályból.
- A GUID nem elsődleges kulcs. Az adatbázis-identitás az `id`, a GUID csak külső foglalási/QR kód.

Rollback:
- `down()` esetben előbb `Schema::dropIfExists('table_sessions')`, utána `Schema::dropIfExists('tables')`.
- Mivel új tábla, meglévő adatot nem migrál át.

Adatkockázat:
- Nincs meglévő adatvesztési kockázat.
- Soft delete miatt törölt asztalok visszaállíthatók lehetnek, de törölt asztal GUID-ja továbbra is foglalhatja az egyedi indexet. Ez elfogadható első körben.

## Model terv

Új model:
- `App\Models\Table`
- `App\Models\TableSession`

Megjegyzés:
- A `Table` modelnév PHP-ben és Laravel namespace alatt nem ütközik. SQL-ben a tábla neve `tables`, ezért nincs kulcsszóütközés.
- A modelben `protected $table = 'tables'` opcionálisan megadható az egyértelműség kedvéért, de a Laravel névkonvenció alapján a `Table` modelhez alapból is `tables` tartozik.

Trait:
- `HasFactory`
- `SoftDeletes`

Fillable:
- `name`
- `guid`
- `active`

Cast:
- `active`: boolean
- `deleted_at`: datetime

Kapcsolatok:
- `sessions()`: hasMany `TableSession`
- `openSession()`: hasOne `TableSession`, `status=open`, `closed_at=null`

`TableSession` kapcsolatok:
- `table()`: belongsTo `Table`
- `ownerGuest()`: belongsTo `Guest`

## Controller/API terv

Új controller javaslat:
- `App\Http\Controllers\TableController`

Első körben service külön osztály nem kötelező, de a foglalási tranzakciót érdemes privát/controllerből hívott metódusba vagy később service-be tenni. Ha a controller túl nő, a foglalási logika `TableService` osztályba vihető.

### Staff/admin endpointok

Route-ok `routes/staff.php` alatt, `auth:guard_employee` middleware-en belül:

- `GET /api/staff/tables`
- `POST /api/staff/tables`
- `GET /api/staff/tables/{table}`
- `PUT /api/staff/tables/{table}`
- `DELETE /api/staff/tables/{table}`
- `POST /api/staff/tables/{table}/regenerate-guid`

Request validáció:

`POST /api/staff/tables`
- `name`: required|string|max:64
- `active`: sometimes|boolean

`PUT /api/staff/tables/{table}`
- `name`: sometimes|required|string|max:64
- `active`: sometimes|boolean

`POST /api/staff/tables/{table}/regenerate-guid`
- nincs kötelező request mező első körben
- csak nem foglalt asztalnál engedélyezett

Staff response minimum mezők:
- `id`
- `name`
- `guid`
- `active`
- `status`
- opcionálisan `open_session`

Hibák:
- `404`: nincs ilyen asztal
- `409`: foglalt asztal GUID-ja nem cserélhető
- `422`: validációs hiba

### Guest endpointok

Route-ok `routes/guest.php` alatt, `auth:guard_guest` middleware-en belül:

- `GET /api/guest/tables/available`
- `POST /api/guest/tables/claim`
- `GET /api/guest/tables/current`

Szabad asztalok listája:

`GET /api/guest/tables/available`

Response:

```json
{
  "tables": [
    {
      "id": 12,
      "name": "Asztal 4",
      "guid": "9f2f4d8c-0000-0000-0000-000000000000",
      "status": "available"
    }
  ]
}
```

Megjegyzés:
- A lista csak `active=true`, nem soft deleted és nem foglalt asztalokat adjon vissza.
- A frontend a listából kiválasztott asztal `guid` értékével ugyanazt a claim endpointot hívhatja, mint QR-kód beolvasáskor.
- A vendég számára az asztal neve legyen elsődlegesen látható; a GUID/kód technikai foglalási azonosító.

Claim request:

```json
{
  "guid": "9f2f4d8c-0000-0000-0000-000000000000"
}
```

Claim sikeres response:

```json
{
  "table": {
    "id": 12,
    "name": "Asztal 4",
    "status": "reserved",
    "is_owner": true
  },
  "table_session": {
    "id": 3,
    "table_id": 12,
    "owner_guest_id": 45,
    "business_date": "2026-05-14",
    "opened_at": "2026-05-14T18:30:00+00:00",
    "status": "open"
  }
}
```

Claim hibák:
- `404`: nincs ilyen GUID
- `409`: az asztal már foglalt
- `409`: a vendég már felelőse másik aktív asztalnak
- `403`: látogató nem foglalhat, csak bejelentkezett vendég
- `422`: hiányzó vagy hibás formátumú GUID

`GET /api/guest/tables/current` response:
- ha van aktuális felelősi asztal: `table` és `table_session` objektum
- ha nincs aktuális asztal: `{ "table": null, "table_session": null }`

Megjegyzés: a Brief 02 után approved asztaltag is ezt az endpointot használja, de `is_owner=false` jelzéssel.

## Üzleti szabályok

- Csak bejelentkezett vendég foglalhat asztalt.
- Egy vendég egyszerre csak egy nyitott table session felelőse lehet.
- Csak `active=true`, nem soft deleted, nyitott sessionnel nem rendelkező asztal foglalható.
- A vendég szabad asztalokat név szerint listázhat az appban.
- A listából foglalás ugyanazt a backend szabályt használja, mint a QR/GUID alapú foglalás.
- Foglaláskor új `table_sessions` rekord jön létre:
  - `table_id` a foglalt asztal id-ja,
  - `owner_guest_id` az aktuális vendég id-ja,
  - `business_date` az aktuális üzleti/nyitási nap,
  - `opened_at` az aktuális idő,
  - `status = open`,
  - `closed_at = null`.
- Asztal felszabadításakor a nyitott session `status = closed`, `closed_at = now()`.
- Foglalt asztal GUID-ja nem regenerálható.
- GUID regeneráláskor a régi GUID többé nem használható.
- GUID generálásnál `Str::uuid()` vagy Laravel által támogatott UUID-generálás használható.

## Tranzakció és konkurencia

Foglalásnál tranzakció szükséges.

Javaslat:
- GUID alapján asztal lekérése,
- sor zárolása `lockForUpdate()`-tel,
- aktív állapot ellenőrzése,
- asztal nyitott sessionjének ellenőrzése,
- aktuális vendég másik nyitott sessionjének ellenőrzése,
- új table session létrehozása.

Ezzel két párhuzamos claim kérés közül csak az egyik nyerhet.

## Backward Compatibility

Nincs meglévő `tables` API, ezért nincs route-kompatibilitási kényszer.

Meglévő `guests.table` és `guests.reservee` mezők:
- első modulban nem törlendők és nem migrálandók,
- új funkció ne ezekre épüljön,
- későbbi cleanup/refaktor döntés lehet.

## OpenAPI terv

Új schema:
- `TableSchema`
- `TableClaimRequest`
- `TableClaimResponse`

Új path dokumentáció:
- guest szabad asztalok listája
- staff table lista/létrehozás/módosítás/inaktiválás
- staff GUID regenerálás
- guest table claim
- guest current table

Dokumentálandó hibák:
- `403`
- `404`
- `409`
- `422`

## Tesztterv

Új feature teszt javaslat:
- `tests/Feature/Http/Controllers/TableControllerTest.php`

Tesztesetek:
- staff létre tud hozni asztalt, GUID automatikusan létrejön,
- `guid` egyedi és nem üres,
- staff listázni tudja az asztalokat,
- staff módosítani tudja a nevet és aktív státuszt,
- staff inaktiválni vagy soft delete-elni tudja az asztalt,
- vendég név szerint látja a szabad aktív asztalokat,
- vendég listájában nem jelenik meg foglalt, inaktív vagy soft deleted asztal,
- vendég le tud foglalni aktív, szabad asztalt GUID alapján,
- foglalás után nyitott `table_sessions` rekord jön létre,
- foglalt asztal másik vendégnek `409`,
- ugyanaz a vendég nem foglalhat második aktív asztalt,
- ugyanaz az asztal újra foglalható, ha az előző session lezárult,
- ugyanaz az asztal ugyanazon üzleti napon belül is újra foglalható lezárt előző session után,
- inaktív asztal nem foglalható,
- nem létező GUID `404`,
- hibás request `422`,
- foglalt asztal GUID regenerálása `409`,
- szabad asztal GUID regenerálása sikeres, régi GUID nem működik.

Pesszimista tesztek:
- asztal létrehozás üres payload esetén `422`,
- asztal létrehozás hiányzó `name` mezővel `422`,
- asztal módosítás üres vagy hibás `name` értékkel `422`,
- claim üres payload esetén `422`,
- claim hiányzó `guid` mezővel `422`,
- claim rossz formátumú `guid` mezővel `422`,
- claim nem létező GUID-dal `404`,
- claim inaktív asztalra `409` vagy üzleti hiba response,
- claim soft deleted asztalra `404`,
- claim authentikáció nélkül `401`,
- claim staff tokennel vagy hibás guard/user modellel tiltott,
- staff endpoint guest tokennel tiltott,
- GUID regenerálás nem létező asztalra `404`,
- GUID regenerálás foglalt asztalra `409`.

Első célzott ellenőrzés:

```bash
docker compose exec php vendor/bin/phpunit --filter=TableControllerTest
```

Route ellenőrzés:

```bash
docker compose exec php php artisan route:list
```

## Frontend TODO hatás

A frontend TODO-ban már szerepel:
- asztal törzsadat API,
- asztal GUID regenerálás API,
- asztal foglalás API.
- szabad asztalok név szerinti listázása és listából indított foglalás.

Implementáció után pontosítani kell:
- pontos endpoint nevek,
- request/response mezők,
- hibakódok,
- aktuális asztal lekérdezés response formája.

## Implementációs sorrend

1. Migration + model + factory.
2. Feature teszt skeleton és fő claim tesztek.
3. Controller + route-ok.
4. Staff CRUD tesztek.
5. GUID regenerálás teszt és implementáció.
6. OpenAPI schema/path dokumentáció.
7. Frontend TODO pontosítás.
8. Dockeres célzott teszt és route lista.

## Döntési állapot

Üzleti döntés nem maradt nyitva ehhez a modulhoz.

Technikai pontosítás implementáció előtt:
- Nincs nyitott technikai döntés.

Döntések:
- `Table` legyen a model neve.
- `GET /api/guest/tables/current` üres állapotban `{ "table": null, "table_session": null }` választ ad.
