# Brief 03 - Rendelés státusz és asztalhoz kötött rendelés

## Cél

A harmadik modul célja, hogy a rendelés leadása és kezelése illeszkedjen az új `table_sessions` és `table_members` modellhez.

A modul után:
- a vendég saját rendelést vagy aktuális asztalhoz kapcsolt rendelést tud leadni,
- az asztal felelőse és approved tagja az aktuális nyitott table sessionhöz rendelhet,
- a `can_order=false` tag nem adhat le asztalhoz kötött rendelést,
- a rendelés explicit, gépi állapotmezőt kap,
- a rendelési tételek külön fizetési státuszt kapnak,
- a meglévő staff rendeléskezelés az új státuszmezővel együtt is működik.

## Risk Tier

Tier 2.

Indok:
- meglévő rendelési táblákat és endpointokat érint,
- új foreign key kapcsolat kerül a rendeléshez,
- rendelési jogosultságot vezet be asztaltagság alapján,
- a későbbi fizetés/nyugta modul erre fog épülni,
- rossz jogosultsági kezelés esetén vendég más asztalához rendelhetne.

## Jelenlegi állapot

Jelenleg az `orders` tábla időbélyegekből vezeti le az állapotot:
- `recorded_at`
- `made_by`, `made_at`
- `served_by`, `served_at`

Jelenlegi rendelés-asztal kapcsolat:
- `orders.table`: nullable string, nincs kapcsolatban az új `tables` vagy `table_sessions` modellel.

Jelenlegi fizetettség:
- `order_details.receipt_id` jelzi, ha a tétel nyugtára került,
- nincs külön gépi `payment_status` mező.

## Érintett táblák

Meglévő táblák:
- `orders`
- `order_details`
- `guests`
- `employees`
- `table_sessions`
- `table_members`

Új tábla első körben nem szükséges.

## Migration terv

Új migration: `add_status_and_table_session_to_orders`.

`orders` új mezők:
- `status`: string, default `open`
- `table_session_id`: foreign id, nullable, `table_sessions.id`

Indexek:
- `orders.status`
- `orders.table_session_id`
- `orders.guest_id + status`
- `orders.table_session_id + status`

Új migration: `add_payment_status_to_order_details`.

`order_details` új mező:
- `payment_status`: string, default `pending`

Indexek:
- `order_details.payment_status`
- `order_details.order_id + payment_status`

Rollback:
- `down()` esetben az új indexek és oszlopok törlése.

Adatkockázat:
- meglévő rendelések `status=open` és `payment_status=pending` alapértéket kapnak,
- a régi `orders.table` string mező első körben megmarad visszafelé olvashatóság miatt,
- a régi időbélyeg mezők szintén megmaradnak, mert a meglévő staff folyamat ezekre épül.

## Model terv

`Order` bővítés:
- konstansok:
  - `STATUS_OPEN = 'open'`
  - `STATUS_PREPARING = 'preparing'`
  - `STATUS_READY = 'ready'`
  - `STATUS_SERVED = 'served'`
  - `STATUS_CANCELLED = 'cancelled'`
- `table_session_id` és `status` fillable,
- `tableSession()`: belongsTo `TableSession`.

Megjegyzés:
- A jelenlegi `getStatusAttribute()` accessor ütközni fog a valós `status` oszloppal, ezért át kell alakítani.
- Javaslat: a `status` API-mező gépi érték legyen, nem fordított szöveg.
- Ha kell felületre fordított állapot, az külön mező legyen, például `status_label`.

`OrderDetail` bővítés:
- konstansok:
  - `PAYMENT_STATUS_PENDING = 'pending'`
  - `PAYMENT_STATUS_PAID = 'paid'`
- `payment_status` fillable,
- később a receipt/nyugta modul fizetéskor állítja `paid` értékre.

`TableSession` bővítés:
- `orders()`: hasMany `Order`.

## Jogosultsági szabályok

Vendég rendelés:
- Bejelentkezett vendég asztal nélkül csak azonnali fizetéses saját rendelést adhat le.
- Ha van aktuális nyitott table sessionje ownerként, a rendelés automatikusan ehhez a `table_session_id`-hez kapcsolható.
- Ha approved asztaltag, a rendelés ehhez a `table_session_id`-hez kapcsolható.
- Ha approved tag `can_order=false`, asztalhoz kötött rendelést nem adhat le.
- Pending, denied vagy removed tag nem rendelhet az asztalhoz.

Asztal nélküli rendelés:
- Csak asztalhoz kötött rendelés lehet utólag fizetős.
- Minden asztal nélküli rendelés azonnali fizetéses, függetlenül attól, hogy látogató/anoním felhasználó vagy bejelentkezett vendég indítja.
- Azonnali fizetéses rendelésnél a rendelés csak sikeres fizetés után kerülhet ténylegesen nyitott/készítendő rendelésként a staff folyamatba.

Staff rendelés:
- Pultos/pincér rendelést rögzíthet vendéghez.
- Ha a kiválasztott vendég aktuális table session owner vagy approved tag, a rendelés kapcsolható a table sessionhöz.
- Staff ne kapcsolhasson rendelést lezárt table sessionhöz.

## API terv

### Vendég rendelés leadása

Meglévő endpoint:

`POST /api/guest/orders`

Javasolt request első körben kompatibilisen:

```json
{
  "cart": [
    {
      "drink_id": 1,
      "quantity": 0.5,
      "unit": "liter",
      "ordered_quantity": 2
    }
  ],
  "table_session_id": 3
}
```

Megjegyzés:
- `table_session_id` opcionális.
- Ha nincs megadva, de a vendégnek pontosan egy aktuális table session kontextusa van, a backend automatikusan kapcsolhatja.
- Ha nincs asztalkontextus, a rendelés csak azonnali fizetési folyamattal jöhet létre; utólag fizetős `table_session_id=null` rendelés nem engedélyezett.

Sikeres response:
- a meglévő response maradhat,
- az `order` objektumban jelenjen meg:
  - `status`
  - `table_session_id`
  - a details tételeken `payment_status`.

Hibák:
- `401`: nincs bejelentkezve
- `403`: a vendég nem tagja/felelőse az adott table sessionnek
- `409`: az asztaltag rendelési joga tiltott
- `409`: a table session már nem nyitott
- `422`: hibás cart vagy hibás `table_session_id`

### Staff rendelés rögzítése

Új endpoint javasolt:

`POST /api/staff/orders`

Request:

```json
{
  "guest_id": 12,
  "table_session_id": 3,
  "cart": [
    {
      "drink_id": 1,
      "quantity": 0.5,
      "unit": "liter",
      "ordered_quantity": 2
    }
  ]
}
```

Megjegyzés:
- A route jelenleg nincs bekötve `routes/staff.php` alatt, csak a controller `store()` létezik resource-szerűen.
- Első körben külön staff order create endpoint kell, amely ugyanazt a rendeléstétel-létrehozó logikát használja, mint a guest rendelés.

## Státuszátmenetek

Javasolt gépi állapotok:
- `open`: rendelés rögzítve
- `preparing`: pultos felvette / készíti
- `ready`: elkészült, felszolgálható
- `served`: felszolgálva
- `cancelled`: törölt

Megengedett átmenetek:
- `open` -> `preparing`
- `preparing` -> `ready`
- `ready` -> `served`
- `open` -> `cancelled`
- `preparing` -> `cancelled`

Tiltott átmenetek:
- `served` -> bármi
- `cancelled` -> bármi
- visszalépés normál státuszokra.

Meglévő kompatibilitás:
- `assignOrder()` bartender esetben állítsa `status=preparing`.
- `doneOrder()` bartender esetben állítsa `status=ready`.
- `assignOrder()` waiter esetben csak `ready` állapotú rendelést vehessen fel.
- `doneOrder()` waiter esetben állítsa `status=served`.

## OpenAPI terv

Frissítendő sémák:
- `Order`
- `OrderWithDetails`
- `OrderDetail`
- guest order request schema
- staff order request schema

Új vagy módosuló mezők:
- `orders.status`
- `orders.table_session_id`
- `order_details.payment_status`

Frissítendő path dokumentáció:
- `POST /api/guest/orders`
- `GET /api/guest/orders/{status?}`
- staff order create endpoint
- staff státusz/assign/done endpointok, attól függően, melyik átmeneti API marad.

## Tesztterv

Új vagy bővített feature tesztek:
- `OrderFlowTest`
- új `OrderTableSessionTest` vagy célzott controller teszt

Happy path:
- asztal nélküli vendég csak azonnali fizetéses rendelést adhat le,
- owner rendelést ad le saját table sessionhöz,
- approved tag rendelést ad le table sessionhöz,
- rendelési tételek `payment_status=pending` értékkel jönnek létre,
- rendelés `status=open` értékkel jön létre,
- staff rendelést rögzít vendéghez,
- staff rendelést rögzít approved asztaltag table sessionjéhez.

Pesszimista tesztek:
- auth nélküli guest rendelés `401`,
- üres cart `422`,
- hibás drink unit `422`,
- nem létező table session `422` vagy `404`,
- lezárt table session `409`,
- másik asztal table sessionjéhez rendelés `403`,
- pending tag rendelése `403`,
- denied tag rendelése `403`,
- removed tag rendelése `403`,
- `can_order=false` tag rendelése `409`,
- érvénytelen státuszátmenet `409`,
- served rendelés módosítása `409`,
- cancelled rendelés módosítása `409`.

Első célzott ellenőrzés:

```bash
docker compose exec php vendor/bin/phpunit --filter=OrderFlowTest
```

Kapcsolódó regressziós ellenőrzés:

```bash
docker compose exec php vendor/bin/phpunit --filter='TableControllerTest|TableMemberControllerTest|OrderFlowTest'
docker compose exec php php artisan route:list --path=api/guest/orders
docker compose exec php php artisan route:list --path=api/staff/orders
```

## Frontend TODO hatás

Pontosítandó frontend feladatok:
- rendelés response-ban `status`, `table_session_id`, `payment_status` mezők kezelése,
- asztalhoz kötött rendelésnél aktuális table session kontextus küldése vagy automatikus backend-kapcsolás kezelése,
- `can_order=false` esetben rendelés gomb tiltása,
- staff rendelésfelvétel vendéghez vagy table sessionhöz,
- gépi státuszkód és fordított státuszcímke külön kezelése.

## Implementációs sorrend

1. Migration: `orders.status`, `orders.table_session_id`.
2. Migration: `order_details.payment_status`.
3. Model kapcsolatok és konstansok.
4. Order státusz accessor/refaktor: gépi `status`, opcionális `status_label`.
5. Rendeléslétrehozó közös privát/helper metódus a cart feldolgozásához.
6. Guest order flow table session jogosultsággal.
7. Staff order create endpoint.
8. Staff státuszátmenetek igazítása.
9. Pesszimista tesztek.
10. OpenAPI frissítés.
11. Frontend TODO pontosítás.

## Döntési állapot

Elfogadott döntések:
- Rendelés-szintű `paid` állapot nem elsődleges forrás.
- Csak asztalhoz kötött rendelés lehet utólag fizetős; minden asztal nélküli rendelés azonnali fizetéses.
- Fizetettség rendelési tételeken és nyugtakapcsolaton számolódjon.
- Rendelési tételek kapjanak `payment_status` mezőt.
- Fizetéskor a kijelölt tételek `paid` státuszt kapnak.

## Döntési kérdések

1. Asztal nélküli vendég rendelése:
   - Továbbra is engedjük-e a `table_session_id=null` rendelés létrehozását, ha a fizetés még nincs implementálva, vagy most csak asztalhoz kötött rendelések legyenek aktívak?
   A fizetéshez készüljön végpont, ami egyelőre sikerrel záruljon.

2. `orders.table` string mező megtartása:
   - A régi `orders.table` mezőt megtartjuk-e a visszafelé kompatibilitás érdekében, vagy a további kódokban már csak az új `table_session_id`-t használjuk?
   Döntés: Ha redundáns, nincs rá szükség, de jelezzük a frontend felé a változást.
3. Vendég rendelés automatikus asztal-kapcsolása:
   - Ha a vendégnek pontosan egy nyitott `table_session`-je van, automatikusan kapcsoljuk-e a rendelést ehhez a sessionhöz, ha a requestben nem ad meg `table_session_id`-t?
   Döntés: Ha megad `table_session_id`-t akkor az asztalhoz megy a rendelés, egyébként magának fizeti rögtön, mintha nem lenne asztala.
4. Staff rendelés létrehozás:
   - Staff-endpointnál kötelező legyen-e `guest_id`, vagy engedélyezzük-e staff által létrehozott vendég nélküli rendelést (pl. azonnali számlapéldány)?
5. `status` initial érték:
   - A rendelés újonnan mindig `open` legyen, vagy lehet-e `preparing`/`ready` közvetlenül staff oldali rendelésfelvételnél?`
   Döntés: mindig `open` legyen. Amikor a személyzet elkezdi készíteni, akkor billen át `preparing`-ra.
- Sikertelen/félbehagyott fizetés nem módosítja a tételek `payment_status` értékét.
  Döntés: igen. Csak a kiegyenlített rendeléshez kapcsolódó tételek állnak át fizetettre.
- A meglévő rendelés endpointok változhatnak, ha ezt a frontend TODO követi.
  Döntés: így van. a ../frontend/TODO.md fájlban.

Nyitott döntések:
- A guest `POST /api/guest/orders` automatikusan kapcsolja-e az aktuális table sessiont, vagy a frontendnek mindig expliciten küldenie kell a `table_session_id` mezőt?
  Döntés: legyen rá opció (query string) type=personal(defaut)|table|all guest=mine(default)|table
  
- Staff rendelésnél kötelező legyen-e a `table_session_id`, ha a vendég éppen asztaltag/felelős, vagy maradhat saját rendelés?
  Döntés: Maradhat saját rendelés, de lehet asztalhoz is staff által rendelni.
- A `cancelled` rendelés fizetetlen tételekkel hogyan kapcsolódik majd az asztalzárás szabályához: töröltként nem számít függő tételnek, vagy külön sztornó/audit kell hozzá?
- Döntés: Cancelled - amíg `open` a rendelés, addig nincs audit, utána kell hozzá sztornó/audit, kiváltképp, ha fizetett.
