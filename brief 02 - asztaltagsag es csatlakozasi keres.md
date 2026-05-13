# Brief 02 - Asztaltagság és csatlakozási kérés

## Cél

A második modul célja, hogy egy már lefoglalt asztalhoz más bejelentkezett vendégek csatlakozási kérelmet küldhessenek, az asztal felelőse pedig ezeket jóváhagyhassa vagy elutasíthassa.

A modul után:
- bejelentkezett vendég QR/GUID alapján csatlakozási kérelmet tud küldeni egy foglalt asztalhoz,
- az asztal felelőse látja a pending kérelmeket,
- az asztal felelőse jóváhagyhatja vagy elutasíthatja a kérelmet,
- jóváhagyott tag megjelenik az asztal taglistájában,
- jóváhagyott tag aktuális asztalként látja az asztalt `is_owner=false` jelzéssel,
- felelős tudja engedélyezni vagy tiltani egy tag rendelési jogát,
- tiltott/denied tag nem kérhet újra azonnal,
- látogató nem lehet asztaltag,
- a felelősség átadása nem része ennek a modulnak.

## Risk Tier

Tier 2.

Indok:
- új perzisztens adatmodell és migration készül,
- jogosultsági és szerepkör alapú szabályokat érint,
- későbbi rendelési és fizetési jogosultságok erre fognak épülni,
- rossz jogosultsági kezelés esetén vendég más asztalának adatait vagy tételeit érhetné el.

## Érintett táblák

Új tábla:
- `table_members`

Meglévő táblák:
- `tables`
- `table_sessions`
- `guests`

Kapcsolatok:
- `table_members.table_session_id` -> `table_sessions.id`
- `table_members.guest_id` -> `guests.id`
- `table_members.approved_by_guest_id` -> `guests.id`, nullable

## Migration terv

Új migration: `create_table_members_table`.

Mezők:
- `id`: primary key
- `table_session_id`: foreign id, kötelező
- `guest_id`: foreign id, kötelező
- `role`: string vagy enum jellegű string, értékek: `owner`, `member`
- `status`: string vagy enum jellegű string, értékek: `pending`, `approved`, `denied`, `removed`
- `can_order`: boolean, default `true`
- `approved_by_guest_id`: foreign id, nullable
- `approved_at`: datetime/timestamp, nullable
- `removed_at`: datetime/timestamp, nullable
- `created_at`, `updated_at`

Indexek:
- `id`: primary key
- `table_session_id`
- `guest_id`
- `approved_by_guest_id`
- `table_session_id + guest_id`: unique index
- `table_session_id + status`

Megjegyzés:
- A `table_session_id + guest_id` unique index biztosítja, hogy ugyanahhoz az aktuális asztalhasználathoz ugyanaz a vendég ne kapjon több párhuzamos tagsági rekordot.
- Elutasításkor a sima, egyszeri `pending` kérelem törölhető. Tiltott/kéretlen esetben `denied` rekord marad, hogy a vendég ne tudjon azonnal újra kérelmet küldeni.
- A tulajdonos/felelős első körben nem külön tagsági rekordként kötelező, mert a `table_sessions.owner_guest_id` az elsődleges forrás. A response-okban viszont az owner ugyanúgy megjelenhet a taglistában virtuális vagy összeállított elemként.
- A tagság mindig konkrét table sessionhöz tartozik, nem közvetlenül az asztal törzsadathoz. Így ugyanaz az asztal ugyanazon napon később új társasággal tiszta taglistát kap.

Rollback:
- `down()` esetben `Schema::dropIfExists('table_members')`.
- Új tábla, meglévő adatot nem migrál át.

Adatkockázat:
- Nincs meglévő adatvesztési kockázat.
- Későbbi rendelések asztaltagsági jogosultságai erre fognak épülni, ezért a státuszok és indexek visszafelé kompatibilitását későbbi moduloknál figyelni kell.

## Model terv

Új model:
- `App\Models\TableMember`

Trait:
- `HasFactory`

Konstansok:
- `ROLE_OWNER = 'owner'`
- `ROLE_MEMBER = 'member'`
- `STATUS_PENDING = 'pending'`
- `STATUS_APPROVED = 'approved'`
- `STATUS_DENIED = 'denied'`
- `STATUS_REMOVED = 'removed'`

Fillable:
- `table_session_id`
- `guest_id`
- `role`
- `status`
- `can_order`
- `approved_by_guest_id`
- `approved_at`
- `removed_at`

Cast:
- `can_order`: boolean
- `approved_at`: datetime
- `removed_at`: datetime

Kapcsolatok:
- `tableSession()`: belongsTo `TableSession`
- `guest()`: belongsTo `Guest`
- `approvedByGuest()`: belongsTo `Guest`

TableSession model bővítés:
- `members()`: hasMany `TableMember`
- `approvedMembers()`: hasMany `TableMember`, `status=approved`
- `pendingMembers()`: hasMany `TableMember`, `status=pending`

Guest model bővítés:
- `tableMemberships()`: hasMany `TableMember`

## Controller/API terv

A meglévő `TableController` bővíthető, de a tagsági logika átláthatósága miatt új controller javasolt:
- `App\Http\Controllers\TableMemberController`

Route-ok `routes/guest.php` alatt, `auth:guard_guest` middleware-en belül.

### Csatlakozási kérelem létrehozása

`POST /api/guest/tables/join`

Request:

```json
{
  "guid": "9f2f4d8c-0000-0000-0000-000000000000"
}
```

Sikeres response:

```json
{
    "membership": {
    "id": 5,
    "table_session_id": 3,
    "guest_id": 34,
    "role": "member",
    "status": "pending",
    "can_order": true
  }
}
```

Hibák:
- `404`: nincs ilyen GUID vagy soft deleted asztal
- `409`: az asztal nem foglalt, ezért nincs kihez csatlakozni
- `409`: az asztal inaktív
- `409`: a vendég az asztal felelőse, ezért nem kérhet tagságot
- `409`: a vendégnek már van pending vagy approved tagsága ehhez az asztalhoz
- `409`: a vendég denied státuszban van ehhez az asztalhoz
- `422`: hiányzó vagy hibás formátumú GUID

### Aktuális asztal taglistája

`GET /api/guest/tables/current/members`

Jogosultság:
- csak az aktuális asztal felelőse vagy approved tagja kérheti le.

Response:

```json
{
  "members": [
    {
      "id": null,
      "guest_id": 1,
      "name": "Owner Name",
      "role": "owner",
      "status": "approved",
      "can_order": true
    },
    {
      "id": 5,
      "guest_id": 34,
      "name": "Member Name",
      "role": "member",
      "status": "approved",
      "can_order": true
    }
  ],
  "pending": [
    {
      "id": 6,
      "guest_id": 35,
      "name": "Pending Name",
      "role": "member",
      "status": "pending",
      "can_order": true
    }
  ]
}
```

Megjegyzés:
- `pending` listát csak az owner kapjon.
- approved tag saját jogosultsággal a jóváhagyott tagokat láthatja, pending kérelmek nélkül.

### Aktuális asztal lekérése tagság alapján

`GET /api/guest/tables/current`

Hatás:
- az owner a saját nyitott table sessionjét kapja vissza `is_owner=true` jelzéssel,
- approved tag a kapcsolódó nyitott table sessiont kapja vissza `is_owner=false` jelzéssel,
- asztal nélküli vendégnél `table=null` és `table_session=null`.

### Kérelem jóváhagyása

`POST /api/guest/tables/members/{member}/approve`

Jogosultság:
- csak az adott asztal felelőse.

Hatás:
- `status = approved`
- `approved_by_guest_id = owner id`
- `approved_at = now()`
- `can_order = true`

Hibák:
- `404`: nincs ilyen tagsági rekord
- `403`: nem az asztal felelőse
- `409`: nem pending státuszú rekord

### Kérelem elutasítása

`POST /api/guest/tables/members/{member}/reject`

Jogosultság:
- csak az adott asztal felelőse.

Hatás:
- egyszerű pending kérelem törölhető.

Response:
- `204 No Content`

Hibák:
- `404`: nincs ilyen tagsági rekord
- `403`: nem az asztal felelőse
- `409`: nem pending státuszú rekord

### Tag rendelési jogának tiltása/engedélyezése

`POST /api/guest/tables/members/{member}/toggle-ordering`

Request:

```json
{
  "can_order": false
}
```

Jogosultság:
- csak az adott asztal felelőse.

Hatás:
- approved tag `can_order` mezője módosul.

Hibák:
- `404`: nincs ilyen tagsági rekord
- `403`: nem az asztal felelőse
- `409`: nem approved státuszú rekord
- `422`: hiányzó vagy nem boolean `can_order`

### Tag eltávolítása

`DELETE /api/guest/tables/members/{member}`

Jogosultság:
- csak az adott asztal felelőse.

Hatás:
- `status = removed`
- `removed_at = now()`
- `can_order = false`

Megjegyzés:
- Eltávolított tag később újra kérhet csatlakozást, ha nem denied státuszban van.
- Első körben az újrakéréshez a removed rekord újra pending állapotba tehető, vagy a removed rekord törölhető és új rekord jöhet létre. Javaslat: removed rekord újrahasznosítása pending állapotra, mert a unique index így egyszerű marad.

Hibák:
- `404`: nincs ilyen tagsági rekord
- `403`: nem az asztal felelőse
- `409`: owner nem távolítható el tagsági endpointon

## Üzleti szabályok

- Csak bejelentkezett vendég kérhet csatlakozást.
- Látogató nem lehet asztaltag.
- Csak foglalt, aktív, nem soft deleted asztalhoz lehet csatlakozást kérni.
- A csatlakozás mindig az asztal aktuális nyitott table sessionjéhez történik.
- Szabad asztalhoz nem csatlakozni kell, hanem claim/foglalás történik.
- Az asztal felelőse nem kérhet saját asztalához tagságot.
- Egy vendég ugyanahhoz a table sessionhöz egyszerre csak egy tagsági rekorddal rendelkezhet.
- Ugyanazon asztal későbbi, új table sessionjéhez a vendég újra kérhet csatlakozást.
- Pending vagy approved tagság mellett nem lehet új csatlakozási kérelmet létrehozni.
- Denied státusz mellett nem lehet új kérelmet létrehozni.
- Removed státusz után új kérelem indítható, ha nem denied.
- Csak az asztal felelőse hagyhat jóvá, utasíthat el, távolíthat el és állíthat rendelési jogot.
- A felelősség másik tagnak átadása későbbi fejlesztés.
- A tag rendelési jogosultságát a későbbi rendelés modulnak ellenőriznie kell.

## Tranzakció és konkurencia

Csatlakozási kérelem létrehozásánál tranzakció javasolt.

Javaslat:
- GUID alapján asztal lekérése,
- asztal sor zárolása `lockForUpdate()`-tel,
- nyitott table session lekérése,
- meglévő tagsági rekord ellenőrzése `table_session_id + guest_id` alapján,
- pending/approved/denied/removed státusz szerinti döntés,
- rekord létrehozása vagy removed rekord pendingre állítása.

Approve/reject/toggle/remove műveleteknél:
- tagsági rekord sor zárolása,
- owner jogosultság ellenőrzése,
- státusztranzíció ellenőrzése,
- mentés/törlés.

## Backward Compatibility

Nincs meglévő asztaltagság API, ezért route-kompatibilitási kényszer nincs.

Az első modul `table_sessions.owner_guest_id` mezője marad az owner/felelős elsődleges forrása.

## OpenAPI terv

Új schema:
- `TableMember`
- `TableJoinRequest`
- `TableMemberResponse`
- `TableMembersResponse`
- `ToggleTableMemberOrderingRequest`

Új path dokumentáció:
- guest table join
- guest current table members
- guest approve member
- guest reject member
- guest toggle member ordering
- guest remove member

Dokumentálandó hibák:
- `401`
- `403`
- `404`
- `409`
- `422`

## Tesztterv

Új feature teszt javaslat:
- `tests/Feature/Http/Controllers/TableMemberControllerTest.php`

Happy path tesztek:
- vendég csatlakozási kérelmet tud küldeni foglalt aktív asztalhoz GUID alapján,
- owner látja a pending kérelmet,
- owner jóváhagyja a pending kérelmet,
- approved tag megjelenik a taglistában,
- owner elutasít pending kérelmet és a rekord törlődik,
- owner tiltja/engedi approved tag rendelési jogát,
- owner eltávolít approved tagot removed státuszra.

Pesszimista tesztek:
- auth nélküli join `401`,
- staff token guest join endpointon `401`,
- join üres payload esetén `422`,
- join hiányzó `guid` mezővel `422`,
- join rossz GUID formátummal `422`,
- join nem létező GUID-dal `404`,
- join szabad asztalhoz `409`,
- join inaktív asztalhoz `409`,
- join soft deleted asztalhoz `404`,
- owner nem kérhet tagságot saját asztalához `409`,
- már pending vendég nem küldhet új kérelmet `409`,
- már approved vendég nem küldhet új kérelmet `409`,
- denied vendég nem küldhet új kérelmet `409`,
- nem owner nem hagyhat jóvá kérelmet `403`,
- nem owner nem utasíthat el kérelmet `403`,
- nem owner nem állíthat rendelési jogot `403`,
- approve nem pending rekordra `409`,
- reject nem pending rekordra `409`,
- toggle nem approved rekordra `409`,
- toggle hiányzó vagy rossz `can_order` értékkel `422`,
- owner tagsági endpointon nem távolítható el `409`.

Első célzott ellenőrzés:

```bash
docker compose exec php vendor/bin/phpunit --filter=TableMemberControllerTest
```

Kapcsolódó regressziós ellenőrzés:

```bash
docker compose exec php vendor/bin/phpunit --filter=TableControllerTest
docker compose exec php php artisan route:list
```

## Frontend TODO hatás

A frontend TODO-ban már szerepel az asztal tagság kezelése.

Implementáció után pontosítani kell:
- join endpoint pontos request/response formája,
- pending és approved taglista response formája,
- owner-only műveletek hibakezelése,
- rendelési jog toggle UI szerződése,
- denied/removed státuszok UI kezelése.

## Implementációs sorrend

1. Migration + model + factory.
2. Table és Guest model kapcsolatok.
3. Feature teszt skeleton és join happy path.
4. Join controller + route.
5. Owner jogosultság helper/metódus.
6. Members lista + approve/reject/toggle/remove endpointok.
7. Pesszimista tesztek.
8. OpenAPI schema/path dokumentáció.
9. Frontend TODO pontosítás.
10. Dockeres célzott teszt és route lista.

## Döntési állapot

Üzleti döntés nem maradt nyitva ehhez a modulhoz.

Döntések:
- `table_members` legyen az új tábla neve.
- A csatlakozási request vendég oldalon GUID-ot kapjon, ne belső `table_id` értéket.
- A tagság `table_session_id` alapján kapcsolódjon, ne közvetlen `table_id` alapján.
- A felelős/owner elsődleges forrása továbbra is `table_sessions.owner_guest_id`.
- A pending tagrekord létrejön csatlakozási kérelemkor.
- Egyszerű elutasításkor a pending tagrekord törölhető.
- Denied tiltó állapot külön státuszként megmarad.
- Removed tag újra kérhet csatlakozást, ha nem denied.
- Felelősség átadása nem része ennek a modulnak.

Nyitott technikai döntés:
- Nincs.
