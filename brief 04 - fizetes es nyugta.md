# Brief 04 - Fizetés és nyugta

## Cél

A negyedik modul célja, hogy a brief 03-ban bevezetett `order_details.payment_status` mezőre építve létrejöjjön a szerveroldali fizetési és nyugtakezelési alap.

A modul után:
- vendég saját függő rendelési tételeit fizetheti,
- approved asztaltag az aktuális nyitott table session függő tételeiből fizethet,
- asztal felelőse záró fizetést indíthat az összes fennmaradó függő tételre,
- sikeres fizetéskor nyugta jön létre,
- a fizetett rendelési tételek `payment_status=paid` értékre és `receipt_id` kapcsolatra váltanak,
- sikertelen vagy félbehagyott fizetés nem módosítja a rendelési tételek fizetési státuszát,
- minden fizetési állapotváltozás auditált eseményként rögzül,
- staff/admin fizetettnek jelölhet tételt auditált admin beavatkozásként.

## Risk Tier

Tier 2/3.

Indok:
- pénzügyi és audit jellegű folyamat,
- meglévő `receipts` és `order_details` kapcsolatot érint,
- új perzisztens fizetési próbálkozás és eseménynapló kerül be,
- rossz jogosultsági kezelés esetén vendég más rendelését fizethetné vagy láthatná,
- rollback során pénzügyi/audit adatok elvesztése kockázatos, ezért éles adaton külön körültekintést igényel.

## Jelenlegi állapot

Jelenleg:
- `receipts` tábla létezik,
- `ReceiptController` alap CRUD jelleggel tud nyugtát létrehozni,
- receipt route-ok nincsenek üzleti fizetési folyamatként bekötve,
- `order_details.receipt_id` már létező kapcsolat a nyugtára,
- `order_details.payment_status` brief 03 alapján létezik, értékei: `pending`, `paid`,
- rendelés-szintű `paid` állapot nincs és nem is elsődleges fizetettségi forrás,
- fizetettséget rendelési tétel szinten kell kezelni,
- fizetési próbálkozás és payment event tábla még nincs.

## Érintett táblák

Meglévő táblák:
- `receipts`
- `order_details`
- `orders`
- `guests`
- `employees`
- `table_sessions`
- `table_members`

Új táblák:
- `payment_attempts`
- `payment_events`

Várhatóan bővítendő meglévő tábla:
- `receipts`

## Adatmodell és migration terv

### `payment_attempts`

Új migration: `create_payment_attempts_table`.

Javasolt mezők:
- `id`
- `guest_id`, nullable: fizetést indító vendég, ha vendég indította
- `employee_id`, nullable: fizetést rögzítő staff/admin, ha személyzet indította
- `table_session_id`, nullable: asztalhoz kötött fizetésnél
- `receipt_id`, nullable: sikeres fizetés után létrejött nyugta
- `status`: `pending`, `succeeded`, `failed`, `abandoned`
- `payment_method`: `cash`, `card`, `admin_marked_paid`
- `amount`: integer, fillér/forint egész összegként a jelenlegi `unit_price` mintát követve
- `currency`: string, default `HUF`
- `started_at`, nullable
- `finished_at`, nullable
- timestamps

Indexek:
- `guest_id + status`
- `employee_id + status`
- `table_session_id + status`
- `receipt_id`
- `status`

Rollback:
- tábla törlése.

Adatkockázat:
- éles fizetési próbálkozás törlése auditvesztés, ezért rollback csak fejlesztői/teszt környezetben biztonságos.

### `payment_events`

Új migration: `create_payment_events_table`.

Javasolt mezők:
- `id`
- `payment_attempt_id`
- `event_type`: string
- `actor_guest_id`, nullable
- `actor_employee_id`, nullable
- `order_detail_id`, nullable
- `receipt_id`, nullable
- `audit_xml`, nullable text
- `created_at`

Indexek:
- `payment_attempt_id + created_at`
- `event_type`
- `actor_guest_id`
- `actor_employee_id`
- `receipt_id`

Rollback:
- tábla törlése.

Adatkockázat:
- audit esemény törlése visszakövethetőségi veszteség, éles környezetben kerülendő.

### `receipts` bővítés

Új migration: `add_payment_context_to_receipts`.

Javasolt mezők:
- `table_session_id`, nullable
- `payment_attempt_id`, nullable
- `access_guid`, nullable, egyedi, nem kitalálható

Indexek:
- `table_session_id`
- `payment_attempt_id`
- `access_guid` unique nullable

Rollback:
- indexek és új oszlopok törlése.

Adatkockázat:
- meglévő nyugták változatlanul maradnak,
- régi `table` string mező első körben megmarad visszafelé olvashatóság miatt,
- `access_guid` csak nyugta-hozzáférésre szolgálhat, rendelésmódosításra nem.

## Model terv

Új modellek:
- `PaymentAttempt`
- `PaymentEvent`

`PaymentAttempt` konstansok:
- `STATUS_PENDING = 'pending'`
- `STATUS_SUCCEEDED = 'succeeded'`
- `STATUS_FAILED = 'failed'`
- `STATUS_ABANDONED = 'abandoned'`
- `METHOD_CASH = 'cash'`
- `METHOD_CARD = 'card'`
- `METHOD_ADMIN_MARKED_PAID = 'admin_marked_paid'`

`PaymentAttempt` kapcsolatok:
- `guest()`: belongsTo `Guest`
- `employee()`: belongsTo `Employee`
- `tableSession()`: belongsTo `TableSession`
- `receipt()`: belongsTo `Receipt`
- `events()`: hasMany `PaymentEvent`

`PaymentEvent` kapcsolatok:
- `paymentAttempt()`: belongsTo `PaymentAttempt`
- `actorGuest()`: belongsTo `Guest`
- `actorEmployee()`: belongsTo `Employee`
- `orderDetail()`: belongsTo `OrderDetail`
- `receipt()`: belongsTo `Receipt`

`Receipt` bővítés:
- `table_session_id`, `payment_attempt_id`, `access_guid` fillable,
- `tableSession()`: belongsTo `TableSession`,
- `paymentAttempt()`: belongsTo `PaymentAttempt`.

`OrderDetail` bővítés:
- `receipt()`: belongsTo `Receipt`.

## Jogosultsági szabályok

Saját rendelés fizetése:
- bejelentkezett vendég csak a saját `orders.guest_id` alatti, `payment_status=pending` tételeket fizetheti,
- más vendég asztal nélküli vagy saját rendelése tiltott.

Asztalhoz kötött fizetés:
- owner és approved member fizethet a saját aktuális nyitott table sessionjének pending tételeiből,
- pending, denied, removed tag nem fizethet asztaltételt,
- `can_order=false` tag fizethet, mert a tiltás rendelésleadásra vonatkozik, nem tartozás rendezésére,
- lezárt table sessionhöz új fizetés nem indítható, kivéve későbbi admin rendezési folyamat, ami első körben nem része ennek a modulnak.

Záró fizetés:
- csak table owner indíthatja,
- minden fennmaradó `payment_status=pending` tételt kiválaszt az aktuális nyitott table sessionből,
- ha nincs pending tétel, 409 vagy no-op helyett javasolt 409, mert nincs fizetendő tartalom.

Staff/admin fizetettnek jelölés:
- staff/admin pending tételeket jelölhet fizetettnek,
- ilyenkor automatikusan létrejön nyugta,
- payment method legyen `admin_marked_paid`,
- minden művelet kapjon `payment_events` audit rekordot.

Anoním vendég:
- az anoním, belépés nélküli rendelési/fizetési folyamat külön szeletet igényel,
- ebben a modulban az `access_guid` előkészíthető a nyugtán,
- publikus GUID-alapú nyugtalekérés külön endpointként csak akkor implementálandó, ha az anoním rendelésfolyamat is pontosítva van.

## Asztalfogyasztási limit

Ebben a modulban előrehozott rendelési védőszabály:
- az owner beállíthat table session szintű asztallimitet,
- a staff limit alapértelmezése konfigurációból jön,
- admin session szinten felülírhatja a konfigurált staff limitet,
- ha owner limit és staff limit is létezik, az alacsonyabb a mérvadó,
- `null` vagy `0` limitérték azt jelenti, hogy az adott oldalról nincs limit,
- a limit a pending/fizetetlen asztaltételek összegére vonatkozik,
- ha az új rendelés túllépné a mérvadó limitet, a rendelés 409 választ kap, és fizetni kell a nyitott tételekből.

Új endpointok:
- `GET /api/guest/tables/current/stats`
- `POST /api/guest/tables/current/spending-limit`
- `POST /api/staff/table-sessions/{tableSession}/spending-limit`

Owner stat response:
- `payable_total`: aktuálisan fizetendő pending asztaltételek összege,
- `effective_spending_limit`: az owner/staff limitek közül a mérvadó limit, vagy `null`,
- `remaining_spending_limit`: hátralévő keret, limit nélkül `null`,
- `per_guest_consumption`: vendégenkénti `total`, `payable_total`, `paid_total`.

Új konfiguráció:
- `tables.default_staff_spending_limit`

## API terv

### Vendég saját pending tételek fizetése

Új endpoint:

`POST /api/guest/payments`

Request:

```json
{
  "order_detail_ids": [1, 2],
  "payment_method": "card"
}
```

Sikeres response:

```json
{
  "payment": {
    "id": 1,
    "status": "succeeded",
    "payment_method": "card",
    "amount": 2200,
    "currency": "HUF",
    "receipt_id": 10
  },
  "receipt": {
    "id": 10,
    "serno": "T000000001",
    "payment_method": "card"
  }
}
```

Hibák:
- `401`: nincs bejelentkezve,
- `403`: más vendég tétele,
- `409`: nincs pending tétel vagy valamelyik tétel már fizetett,
- `422`: hiányzó/hibás `order_detail_ids` vagy `payment_method`.

### Vendég asztaltételek fizetése

Új endpoint:

`POST /api/guest/tables/current/payments`

Request:

```json
{
  "order_detail_ids": [1, 2],
  "payment_method": "card"
}
```

Szabály:
- csak az aktuális nyitott table session pending tételei fizethetők,
- owner és approved member használhatja.

Hibák:
- `403`: nem tagja az asztalnak,
- `409`: nincs aktuális nyitott asztal vagy a tétel nem pending.

### Záró fizetés

Új endpoint:

`POST /api/guest/tables/current/closing-payment`

Request:

```json
{
  "payment_method": "card"
}
```

Szabály:
- csak owner,
- az összes fennmaradó pending asztaltétel bekerül.

### Staff/admin fizetettnek jelölés

Új endpoint:

`POST /api/staff/order-details/mark-paid`

Request:

```json
{
  "order_detail_ids": [1, 2],
  "memo": "Pultnál készpénzben rendezve"
}
```

Sikeres response:
- payment attempt,
- receipt,
- módosított order detail azonosítók.

Megjegyzés:
- a korábbi tervben szereplő `POST /api/staff/orders/{order}/mark-paid` első körben opcionális kényelmi endpoint legyen,
- az alap implementáció tételszintű legyen, mert a fizetettség forrása is tételszintű.

### Nyugta lekérdezés

Új endpoint:

`GET /api/guest/receipts/{receipt}`

Szabály:
- vendég csak saját receiptet láthat,
- asztalnál owner vagy approved member csak akkor láthatja, ha a receipt az aktuális vagy korábbi saját table sessionhöz tartozott és ő jogosult volt a sessionben,
- publikus `access_guid` alapú lekérdezés külön későbbi anoním szelet.

Első körben nem implementálandó:
- `GET /api/guest/receipts/{receipt}/download`,
- `POST /api/guest/receipts/{receipt}/email`.

Ezekhez külön PDF/e-mail döntés kell.

## Fizetési folyamat

Sikeres fizetés:
1. validáció,
2. jogosultsági ellenőrzés,
3. pending tételek zárolása tranzakcióban,
4. `payment_attempts` rekord `pending` státusszal,
5. `payment_events.created`,
6. összeg számítása a tételekből,
7. fizetés sikeresnek jelölése,
8. `receipts` rekord létrehozása,
9. order detail sorok `payment_status=paid`, `receipt_id=<receipt>` frissítése,
10. `payment_events.payment_succeeded`,
11. `payment_events.receipt_created`,
12. response.

Sikertelen fizetés:
1. `payment_attempts` rekord létrejön,
2. `status=failed`,
3. `payment_events.payment_failed`,
4. order detail sorok maradnak `pending` állapotban,
5. receipt nem jön létre.

Első implementációs döntés:
- külső fizetési szolgáltató integráció nincs,
- `card` és `cash` fizetés fejlesztői/üzleti szimulációként azonnal sikeres,
- sikertelen/abandoned státusz teszteléséhez `simulate_result` request mező használható, de csak `APP_ENV=local` és `APP_ENV=testing` környezetben,
- production/staging környezetben a `simulate_result` mező nem valid,
- kártyaelutasítás vagy szolgáltatói hiba oka payment event/audit adatként kerüljön rögzítésre, például `payment_failed` eseménnyel.

## OpenAPI terv

Új sémák:
- `PaymentAttempt`
- `PaymentEvent`
- `PaymentResponse`
- `CreatePaymentRequest`
- `StaffMarkPaidRequest`

Frissítendő sémák:
- `Receipt`
- `OrderDetail`

Új path dokumentáció:
- `POST /api/guest/payments`
- `POST /api/guest/tables/current/payments`
- `POST /api/guest/tables/current/closing-payment`
- `GET /api/guest/receipts/{receipt}`
- `POST /api/staff/order-details/mark-paid`

Nem dokumentálandó első körben:
- nyugta PDF letöltés,
- nyugta e-mail újraküldés,
- anoním GUID-alapú nyugta-hozzáférés,
- külső fizetési provider callback.

## Tesztterv

Új feature teszt javaslat:
- `PaymentFlowTest`

Happy path:
- vendég saját pending tételt fizet,
- sikeres fizetés nyugtát hoz létre,
- fizetett tétel `payment_status=paid`,
- fizetett tétel `receipt_id` értéket kap,
- asztaltag fizethet aktuális asztaltételt,
- owner záró fizetése minden pending asztaltételt rendez,
- staff/admin tételt fizetettnek jelöl és audit event jön létre.

Pesszimista esetek:
- más vendég saját rendelésének fizetése 403,
- nem asztaltag asztaltétel fizetése 403,
- pending/denied/removed member asztaltétel fizetése 403,
- lezárt table session fizetése 409,
- már fizetett tétel ismételt fizetése 409,
- üres `order_detail_ids` 422,
- nem létező order detail 422 vagy 404, endpoint validációs mintától függően,
- vegyes, részben jogosulatlan tétellista teljes tranzakciót elutasít, részfizetés ne történjen,
- sikertelen payment attempt után a tételek pending állapotban maradnak.

Unit/service teszt javaslat:
- amount számítás több tételből,
- payment event létrehozás sorrendje,
- receipt serial generálás egyedisége,
- sikertelen fizetés nem hoz létre receiptet.

## Frontend/API TODO

Frontend oldalon követendő változások:
- új `POST /api/guest/payments`,
- új `POST /api/guest/tables/current/payments`,
- új `POST /api/guest/tables/current/closing-payment`,
- új `GET /api/guest/receipts/{receipt}`,
- új `POST /api/staff/order-details/mark-paid`,
- rendelési tételeken a `payment_status` alapján kell fizethető tételeket listázni,
- receipt response és payment response típusok felvétele,
- nyugta PDF/e-mail funkció első körben ne legyen UI-ként ígérve, amíg nincs backend endpoint.

## Nyitott, de implementációt nem blokkoló későbbi döntések

- PDF nyugta generálás technikai megoldása.
- Nyugta e-mail újraküldés folyamata.
- Külső bankkártyás fizetési szolgáltató integrációja.
- Anoním rendelés és `access_guid` alapú nyugta-visszakeresés pontos UX/API folyamata.
- Sztornó és visszatérítés továbbra is alkalmazáson kívüli folyamat, első körben nincs backend refund API.
