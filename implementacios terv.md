# Backend implementációs terv

## Cél

A cél a backend fokozatos bővítése úgy, hogy minden üzleti vagy API-szerződést érintő döntés még kódolás előtt megtörténjen. A fejlesztés modulonként halad, minden modul előtt döntési kapuval, utána célzott teszttel, OpenAPI frissítéssel és frontend TODO/API interface frissítéssel.

## Alapelv

- Kódolás előtt minden modulhoz rövid implementációs brief készül.
- Ha a briefben nyitott döntés van, nem indul implementáció.
- Migrations/auth/payment/anonimizálás legalább Tier 2 kockázatú.
- Audit adatoknál lehet XML mező/payload olyan adatok megőrzésére, amelyeket nem kell sehol megjeleníteni, de jogi/üzleti visszakövethetőség miatt meg kell őrizni.
- Az audit XML ne legyen általános API response része; csak belső/adminisztratív visszakövetéshez szolgáljon.
- Red -> Green -> Refactor sorrend:
  - először célzott feature/unit teszt vagy legalább tesztterv,
  - minimális implementáció,
  - refaktor,
  - OpenAPI és TODO frissítés,
  - célzott Dockeres ellenőrzés.
- Minden API-változás bekerül:
  - OpenAPI dokumentációba,
  - backend `TODOs.md`-be, ha folyamatot érint,
  - frontend `TODO.md` Backend/API Interfész Változások részébe, ha frontend szerződést érint.
- A unit és feature tesztek mindig tartalmazzanak pesszimista eseteket is: üres adat, hiányzó adat, rossz formátum, érvénytelen referencia, hibás vagy nem megfelelő user modell/jogosultság, valamint üzletileg tiltott állapot.

## Döntési Kapuk

Ezeknél a pontoknál implementáció előtt meg kell állni:

- új adatmodell vagy migráció,
- új jogosultsági szabály,
- rendelés/fizetés/asztal állapotátmenet,
- nyugta vagy fizetés státusza,
- anonimizálás vagy személyes adat módosítása,
- API request/response shape változás,
- frontend által használt route vagy mezőnév változás,
- riport számítási definíció.

## Javasolt Sorrend

### 1. Asztal törzsadat és foglalási alap

Risk tier: Tier 2, mert új perzisztens adatmodell és jogosultsági szabályok kerülnek be.

Cél:
- Adminisztrátor előre regisztrálhat asztalt/helyet/asztalcsoportot.
- Minden asztal kap GUID-szerű foglalási kódot.
- Minden asztalnak van vendégek számára látható neve.
- Vendég appból név szerint listázhatja a szabad asztalokat.
- Vendég GUID/QR alapján lefoglalhat egy szabad asztalt.
- Vendég a szabad asztalok listájából is lefoglalhat asztalt; a frontend ilyenkor ugyanúgy a kiválasztott asztal GUID-ját küldi a backendnek.
- Foglaláskor új `table_sessions` rekord jön létre.
- A foglaló vendég lesz a nyitott table session felelőse.
- Ugyanaz az asztal ugyanazon üzleti/nyitási napon belül is újra foglalható, ha az előző session lezárult.

Érintett várható elemek:
- új migration: `tables`,
- új migration: `table_sessions`,
- új model: `Table`,
- új model: `TableSession`,
- staff/admin CRUD controller,
- guest foglalási endpoint,
- OpenAPI path/schema fájlok,
- feature tesztek.

Javasolt mezők:
`tables`:
- `id`
- `name`
- `code` vagy `guid`, egyedi, nem publikusan kitalálható
- `active`
- timestamps

`table_sessions`:
- `id`
- `table_id`
- `owner_guest_id`
- `business_date`
- `opened_at`
- `closed_at`, nullable
- `status`: `open`, `closed`
- timestamps

Javasolt indexek:
- `tables.id`: primary key
- `tables.guid`: unique index
- `table_sessions.id`: primary key
- `table_sessions.table_id + status`
- `table_sessions.owner_guest_id + status`

API vázlat:
- `GET /api/staff/tables`
- `POST /api/staff/tables`
- `GET /api/staff/tables/{table}`
- `PUT /api/staff/tables/{table}`
- `DELETE /api/staff/tables/{table}` vagy inaktiválás
- `GET /api/guest/tables/available`
- `POST /api/guest/tables/claim`
- `GET /api/guest/tables/current`

Claim request/response:
- `POST /api/guest/tables/claim`
- request: `{ "guid": "<asztal QR/GUID kód>" }`
- sikeres response: az aktuálisan lefoglalt asztal adatai, legalább `id`, `name`, `status`, `is_owner`
- sikeres response tartalmazza a létrejött `table_session` adatait is
- `404`: nincs ilyen GUID
- `409`: az asztal már foglalt
- `409`: a vendég már felelőse másik nyitott table sessionnek
- `403`: látogató nem foglalhat, csak bejelentkezett vendég
- `422`: hiányzó vagy hibás formátumú GUID

Döntések:
- A tábla neve `tables` legyen.
- A törlés soft delete legyen.
- Staff/admin aktiválhat vagy inaktiválhat asztalt `active=true|false` állítással.
- Egy vendég egyszerre csak egy nyitott table session felelőse lehet.
- Egy logikai asztal több fizikai asztalt is képviselhet.
- Egy logikai asztalhoz több GUID/QR kód tartozhat, ha több fizikai asztalból áll.
- Vendég appból lekérheti a szabad asztalok név szerinti listáját.
- A foglaltság elsődleges forrása a nyitott `table_sessions` rekord, nem a `tables` sor.
- A `business_date` üzleti/nyitási napot jelöl.
- A GUID újragenerálható legyen admin által, ha a QR kód kompromittálódott, cserélni kell vagy újranyomtatás történik.
- GUID újrageneráláskor a régi kód ne legyen tovább használható.
- A GUID ne legyen elsődleges kulcs; az asztal belső azonosítója maradjon numerikus vagy UUID `id`, a GUID csak külső foglalási/QR kód.
- Foglalt asztalnál ne lehessen GUID-ot újragenerálni.

Teszt:
- admin létrehoz asztalt,
- vendég lefoglal szabad asztalt,
- foglaláskor nyitott table session jön létre,
- foglalt asztal másik vendégnek 409,
- lezárt table session után ugyanaz az asztal újra foglalható ugyanazon üzleti napon belül is,
- látogató nem foglalhat,
- inaktív asztal nem foglalható.

### 2. Asztaltagság és csatlakozási kérés

Risk tier: Tier 2, mert jogosultságot és rendelési hozzáférést érint.

Cél:
- Vendég QR-kód alapján csatlakozást kérhet egy foglalt asztalhoz.
- Asztal felelőse jóváhagyhatja vagy elutasíthatja.
- Jóváhagyott tag rendelhet az asztalhoz.
- Látogató nem lehet asztaltag.

Érintett várható elemek:
- új migration: `table_members`,
- új model,
- guest endpointok csatlakozási kéréshez,
- felelősi endpointok jóváhagyáshoz,
- tagság lekérdezése.

Javasolt mezők:
- `table_session_id`
- `guest_id`
- `role`: `owner`, `member`
- `status`: `pending`, `approved`, `denied`, `removed`
- `can_order`
- `approved_by_guest_id`
- `approved_at`
- timestamps

API vázlat:
- `POST /api/guest/tables/join`
- `GET /api/guest/tables/current/members`
- `POST /api/guest/tables/members/{member}/approve`
- `POST /api/guest/tables/members/{member}/reject`
- `POST /api/guest/tables/members/{member}/toggle-ordering`
- `DELETE /api/guest/tables/members/{member}`

Döntések:
- Jóváhagyásra váró vendég `pending` tagrekordot kapjon.
- Approved asztaltag a `GET /api/guest/tables/current` endpointon is megkapja az aktuális asztalt `is_owner=false` jelzéssel.
- A csatlakozási request vendég oldalon GUID-ot kapjon, ne belső `table_id` értéket.
- A tagság konkrét `table_session_id`-hez kapcsolódjon, ne közvetlenül a `tables.id` értékhez.
- Elutasításkor a csatlakozási kérés törölhető.
- Kéretlen vagy tiltott csatlakozási kérelemnél maradjon tiltó/denied állapot, hogy ne kérhessen újra azonnal.
- Tag eltávolítása után újra kérhet csatlakozást, ha a felelős nem tiltotta.
- A felelősség másik tagnak átadása későbbi fejlesztés, első körben nem implementálandó.

Teszt:
- QR csatlakozás pending státusszal,
- csak felelős hagyhat jóvá,
- jóváhagyott tag látszik listában,
- látogató nem csatlakozhat,
- tiltott tag nem adhat le asztalhoz rendelést.

### 3. Rendelés státuszmodell és staff rendelésfelvétel

Risk tier: Tier 2, mert meglévő rendelési viselkedést és teszteket érint.

Cél:
- Explicit rendelésállapot bevezetése, ha szükséges.
- Pultos/pincér rendelést rögzíthet vendéghez, vendégen keresztül asztalhoz.
- Állapot csak előre haladhat, kivéve törlés/sztornó.

Javasolt állapotok első körben:
- `open`
- `preparing`
- `ready`
- `served`
- `cancelled`

Rendelési tétel fizetési státusz javasolt:
- `pending`
- `paid`

Fizetési próbálkozás státusz javasolt:
- `pending`
- `succeeded`
- `failed`
- `abandoned`

API vázlat:
- `POST /api/staff/orders`
- `POST /api/staff/orders/{order}/status`
- meglévő `assign`, `done`, `waiting`, `my-tasks` endpointok igazítása vagy kompatibilitási réteg.

Döntések:
- A rendelés kapjon explicit `status` mezőt.
- A kontroller figyelje az érvényes státusztranzíciókat.
- Döntés: ne legyen rendelés-szintű `paid` állapot elsődleges forrásként. A fizetettséget rendelési tétel státuszán és nyugtakapcsolaton kell számolni.
- Döntés: a rendelési tételeken legyen fizetési státusz, első körben `pending` és `paid`.
- Döntés: a sikertelen vagy félbehagyott fizetés a fizetési próbálkozás státuszában jelenjen meg; az érintett rendelési tételek maradjanak `pending` állapotban.
- Döntés: a nyugtára azok a rendelési tételek kerülnek, amelyek fizetett státuszba kerültek; a nyugta tételei természetesen fizetettnek számítanak.
- Döntés: csak asztalhoz kötött rendelés lehet utólag fizetős; minden asztal nélküli rendelés azonnali fizetéses.
- Döntés: a meglévő rendelés endpointok változhatnak, nem kell őket mindenáron kompatibilitási rétegként megtartani.
- Döntés: minden módosított vagy kivezetett rendelés endpoint kerüljön be a frontend TODO-ba utánkövetendő/fejlesztendő API-változásként.

Teszt:
- vendég rendelés alapállapota,
- staff rendelés vendéghez,
- staff rendelés asztaltaghoz,
- érvénytelen állapotugrás 409,
- törlés csak szabály szerint.

### 4. Fizetés és nyugta

Risk tier: Tier 2/3, mert pénzügyi és audit jellegű folyamat.

Cél:
- Nyugta létrejöhet rendeléskor azonnali fizetéssel.
- Nyugta létrejöhet később kijelölt függő tételekre.
- Asztalnál bármely asztaltag fizethet az asztal nyitott tételeiből.
- Záró fizetés minden fennmaradó függő tételt tartalmaz.
- Félbehagyott/sikertelen fizetés státuszt kap, de a tételek függőben maradnak.
- Fizetést visszavonni nem lehet.

Érintett várható elemek:
- receipt route-ok bekötése,
- külön `payments` vagy `payment_attempts` tábla,
- `payment_events` eseménynapló tábla,
- order detail fizetési státusz,
- nyugta tételkapcsolatok,
- audit mezők,
- audit XML payload mező a nem megjelenítendő, de megőrzendő fizetési/admin adatokhoz,
- anoním vendég nyugta-hozzáférési GUID mező vagy külön kapcsolótábla.

API vázlat:
- `POST /api/guest/payments`
- `POST /api/guest/tables/current/payments`
- `POST /api/guest/tables/current/closing-payment`
- `GET /api/guest/receipts/{receipt}`
- `GET /api/guest/receipts/{receipt}/download`
- `POST /api/guest/receipts/{receipt}/email`
- `POST /api/staff/orders/{order}/mark-paid`
- `POST /api/staff/order-details/mark-paid`

Döntések:
- Döntés: a nyugta külön táblában legyen.
- Döntés: fizetéskor a kiválasztott rendelési tételek fizetési státusza íródjon `paid` értékre.
- Döntés: a nyugtára azok a rendelési tételek kerülnek, amelyek a fizetéskor `paid` státuszba kerültek.
- Döntés: legyen külön `payments` vagy `payment_attempts` tábla a fizetési próbálkozások és státuszuk kezelésére.
- Döntés: a fizetéseket eseményszinten naplózni kell. Minden fizetési állapotváltozás és adminisztratív beavatkozás külön `payment_events` rekordot kapjon.
- Döntés: példa payment event típusok: `created`, `items_selected`, `payment_started`, `payment_succeeded`, `payment_failed`, `payment_abandoned`, `marked_paid_by_admin`, `receipt_created`, `receipt_emailed`.
- Döntés: a `payment_events` tartalmazhat XML audit payload mezőt a nem megjelenítendő, de megőrzendő állapot- és külső fizetési adatokhoz.
- Döntés: anoním vendégnél nincs releváns személyes adat; sikeres fizetés után a frontend automatikusan kínálja fel a nyugta letöltését.
- Döntés: anoním vendég kapjon egy nem kitalálható GUID-ot, amelyet a frontend lekérhet és kliens oldalon eltárolhat. Ha a vendég később megadja ezt a GUID-ot, újra megnézheti a hozzá tartozó nyugtáit.
- Döntés: a GUID alapján csak az anoním vendég nyugtái legyenek lekérdezhetők, rendelésmódosításra vagy személyes műveletre ne adjon jogosultságot.
- Döntés: admin fizetettnek jelölésnél automatikusan létrejön nyugta, auditált admin fizetési móddal vagy admin beavatkozás jelöléssel.

Teszt:
- saját függő tétel fizetése,
- más vendég saját rendelésének fizetése tiltott,
- asztaltag fizethet asztaltételt,
- záró fizetés minden függő tételt visz,
- sikertelen fizetés után tétel függő marad,
- nyugta letölthető és e-mail újraküldés kérhető.

### 5. Promóció kalkuláció

Risk tier: Tier 2, mert rendelési árakat és nyugtát érint.

Cél:
- Rendelés véglegesítésekor szerveroldali promóciószámítás.
- Egy tételre egyszerre csak egy promóció érvényesülhet.
- `Egyet fizet kettőt kap` esetén az ingyenes tétel 0 Ft egységáron jelenik meg.
- Auditálni kell promóció azonosítót, eredeti árat, kedvezmény százalékot és összeget.
- A promóciós logika Laravel oldali model/service struktúrában legyen, ne frontend logikában.

Döntések:
- Egy tételre egyszerre csak egy promóció érvényesülhet.
- Több illeszkedő promóció esetén a legnagyobb kedvezmény nyerjen; döntetlen esetén a korábban létrehozott promóció.
- Beégetett promóciók legyenek. A felületen első körben csak aktiválni/deaktiválni lehessen őket, nem kell általános promóció-szerkesztő.
- A promóciók osztályai konfigurációs fájlban legyenek regisztrálva.
- Admin felületen promóciónként csak az legyen állítható, hogy aktív-e, illetve legyen egy szabad szöveges `memo` mező az opciók megadására.
- A `memo` mezőt a rendelésnél a promóciós modell/szolgáltatás is kapja meg.
- A promóciós modell/szolgáltatás kapja meg az egész rendelést és az értékelt rendelési tételt vagy tételeket.
- A promóciós modell/szolgáltatás szerveroldalon állapítsa meg, hogy mely tételeken milyen akciós ár vagy kedvezmény érvényesül.
- Százalékos vagy tételhez kötött kedvezménynél a rendelési tételen legyen eltárolva az eredeti ár, kedvezményes ár, kedvezmény összege és promóció azonosítója.
- Rendelés-szintű vagy több tételt érintő kedvezménynél külön kedvezmény/adjustment rekord javasolt negatív összeggel, ne normál ital rendelési tétel. Így nem kerül be tévesen pult/konyhai teljesítendő tételként, de a nyugtán megjeleníthető.
- Kép és leíró szöveg tartozhat a promócióhoz, hogy a frontend meg tudja jeleníteni.
- A `PromoType` ne általános szabálynyelvként működjön első körben; a konkrét beégetett promóciók saját Laravel osztályban vagy service-ben számoljanak, konfigurációs regisztráció alapján.

Teszt:
- százalékos kategória promóció,
- globális promóció,
- buy-one-get-one promóció,
- több illeszkedő promóció prioritása,
- auditmezők rögzítése rendelési tételen.

### 6. Asztalzárás

Risk tier: Tier 2, mert fizetési és tagsági állapotot zár.

Cél:
- Csak felelős zárhat asztalt.
- Csak akkor zárható, ha nincs függő/fizetetlen tétel.
- Felelős felel a nyitott tételek rendezéséért.

API vázlat:
- `POST /api/guest/tables/current/close`
- `POST /api/staff/tables/{table}/close` admin/staff kivételes rendezéshez, ha szükséges.

Döntések:
- Zárt asztal újra foglalható azonnal.
- Egy foglalt, de rendeléssel nem rendelkező asztal inaktiválható technikai/takarítási okból. Ilyenkor a vendégek és a felelős lekerülnek az asztalról.
- Pultos/pincér csak inaktiválhat vagy újranyithat asztalt.
- Admin zárhat asztalt a felelős helyett auditáltan, kivételes rendezési műveletként.
- Admin asztalzárásnál és GUID újragenerálásnál audit XML-ben megőrizhető a művelet indoka, előző értékek és technikai környezet, ha ezek nem jelennek meg felületen.

Teszt:
- felelős zár fizetett asztalt,
- nem felelős nem zárhat,
- függő tétel mellett 409,
- zárás után asztal újra szabad vagy beállított státuszba kerül.

### 7. GDPR anonimizálás

Risk tier: Tier 2/3, mert személyes adatot és visszafordíthatatlan műveletet érint.

Cél:
- Vendég saját fiókján kezdeményezheti.
- Csak akkor indítható, ha nincs tartozása és nem tagja asztalnak.
- Anoním vendég és GDPR szerint anonimizált vendég külön fogalom.
- Anonimizált fiók nem állítható vissza.

API vázlat:
- `POST /api/guest/me/anonymize`
- `GET /api/guest/me/anonymize/check`

Döntések:
- Inaktivált fiók adminisztrátori segítséggel visszaállítható lehet.
- Anonimizált fiók nem állítható vissza.
- Vezetéknév, utónév és e-mail cím anonimizálandó.
- Az e-mail cím maszkolt formában maradhat.
- Minden további PII törlődjön.
- A művelethez e-mail/confirm token alapú megerősítés kell.
- GDPR műveletnél auditban megőrizhető a művelet ténye, ideje, technikai azonosítói és maszkolt azonosítók, de a törlendő PII ne kerüljön visszamentésre az audit XML-be.

Teszt:
- tartozás mellett tiltott,
- asztaltagság mellett tiltott,
- sikeres anonimizálás maszkolja/törli a mezőket,
- anonimizált vendég nem tud belépni,
- rendelési/nyugta előzmény üzletileg megmarad.

### 8. Utolsó X rendelt ital

Risk tier: Tier 1/2, kisebb adatlekérdezés, de vendégspecifikus.

Cél:
- Kedvencek helyett gyors rendeléshez az utolsó X rendelt ital listázása.

API vázlat:
- `GET /api/guest/recent-drinks?limit=10`

Döntések:
- X alapértelmezett értéke legyen 10.
- Ismétlődő italok egyszer szerepeljenek, a legutóbbi előfordulás szerint rendezve.

Teszt:
- csak saját rendelésekből dolgozik,
- ismétlődő ital egyszer jelenik meg,
- limit működik,
- inaktív ital ne legyen rendelhető gyorslistából, de korábbi előzményként opcionálisan jelölhető.

### 4/a. Asztalfogyasztási limit

Risk tier: Tier 2, mert rendelési jogosultságot és fizetési kényszert érint.

Cél:
- Asztalhoz legyen beállítható fogyasztási limit.
- Legyen owner által beállított limit.
- Legyen konfigurációból érkező alapértelmezett staff limit.
- Az admin session szinten felülírhassa az alapértelmezett staff limitet.
- Ha mindkét limit létezik, mindig az alacsonyabb limit legyen a mérvadó.
- `null` vagy `0` limitérték azt jelenti, hogy az adott oldalról nincs limit.
- A limit a nyitott/függő, még nem fizetett asztaltételek összegére vonatkozzon.
- Ha egy rendelés túllépné a mérvadó limitet, a rendelés előtt fizetni kell a nyitott/függő tételekből.
- Owner számára legyen stat endpoint fizetendő összeggel, mérvadó limittel, hátralévő kerettel és fejenkénti fogyasztással.

API vázlat:
- `GET /api/guest/tables/current/stats`
- `POST /api/guest/tables/current/spending-limit`
- `POST /api/staff/table-sessions/{tableSession}/spending-limit`

Javasolt mezők:
- `table_sessions.owner_spending_limit`, nullable integer
- `table_sessions.staff_spending_limit_override`, nullable integer
- `table_sessions.staff_spending_limit_override_set_by`, nullable employee FK
- `table_sessions.staff_spending_limit_override_set_at`, nullable datetime

Javasolt konfiguráció:
- `tables.default_staff_spending_limit`, nullable integer

Döntések:
- Owner limitet csak az asztal felelőse állíthat.
- Az alapértelmezett staff limit konfigurációból érkezik.
- Session szintű staff limit override-ot csak admin állíthat, auditáltan.
- Ha nincs session override, a konfigurált alapértelmezett staff limit számít.
- `null` és `0` owner/staff limit nem vesz részt a minimum számításban.
- Limit csökkentésekor, ha az aktuális pending fogyasztás már meghaladja az új limitet, új rendelés nem adható le, amíg fizetés nem történik.
- Limit nélküli asztalnál a korábbi rendelési/fizetési szabályok érvényesek.
- A staff limit adminisztratív üzleti kontroll, ezért owner nem írhatja felül.

Teszt:
- owner limit alatt rendelés engedélyezett,
- owner limit felett rendelés 409,
- staff limit és owner limit közül az alacsonyabb érvényesül,
- config staff limit érvényesül session override hiányában,
- admin session override felülírja a config staff limitet,
- `0` limitérték nem blokkol rendelést,
- owner stat tartalmazza a fizetendő összeget, limitet, hátralévő keretet és vendégenkénti bontást,
- részfizetés után újra rendelhető, ha a pending összeg limit alá csökken,
- nem owner nem állíthat owner limitet,
- admin staff-limit override állítás auditált.

### 9. Riportok

Risk tier: Tier 2, aggregált üzleti adatok és exportfájlok.

Cél:
- Backoffice/admin riportok.
- Szűrők: dátumtartomány, kategória, alkalmazott, fizetési mód, promóció.
- CSV export védett mappába.
- Riport metaadat táblában checksum-mal, fájlok könyvtárstruktúrában.

API vázlat:
- `GET /api/staff/reports`
- `POST /api/staff/reports`
- `GET /api/staff/reports/{report}`
- `GET /api/staff/reports/{report}/download`

Döntések:
- Bevétel/fogyás riport alapja a rendelésleadás pillanata.
- Készletet továbbra sem kezelünk, ezért a "visszaállítja a készletet" fogalmazást fogyásriport-korrekcióként kell kezelni, nem készletmodulként.
- Ha törölt rendelés még várakozó állapotban volt, a fogyásriport korrigálható.
- Ha a rendelés már készítés alatt volt, akkor a fogyás szempontjából hulladékként/veszteségként kezelendő.
- Riport generálás queue alapú legyen.

Teszt:
- csak backoffice/admin fér hozzá,
- szűrők működnek,
- CSV létrejön,
- checksum mentődik,
- fájl nem publikus útvonalon érhető el.

## Nyitott Pontok Összefoglaló

Jelenleg nincs üzleti döntésként nyitva hagyott kérdés. Az implementáció előtt modulonként már csak technikai részleteket kell pontosítani: pontos mezőnevek, validációk, hibaválaszok, OpenAPI request/response sémák és tesztesetek.

## Közös Implementációs Lépések Modulonként

1. Brief elkészítése a modulhoz.
2. Döntési kapu: minden nyitott kérdés lezárása.
3. Migrációs kockázat és rollback leírása.
4. API request/response forma rögzítése.
5. Focused teszt megírása vagy frissítése.
6. Implementáció minimális diffel.
7. OpenAPI frissítése.
8. Frontend `TODO.md` frissítése, ha interface változott.
9. Ellenőrzés Dockerben:

```bash
docker compose exec php php artisan route:list
docker compose exec php vendor/bin/phpunit --filter=<RelevantTest>
```

## Következő Modul

Folytassuk a **2. Asztaltagság és csatlakozási kérés** modullal. Ez az asztalfoglalásra épül, és előkészíti a későbbi asztalhoz kötött rendelést és fizetést.

Modul brief:
- `brief 02 - asztaltagsag es csatlakozasi keres.md`

Brief státusz:
- üzleti döntésként nincs nyitott kérdés,
- implementáció előtt már csak a brief review szükséges.
