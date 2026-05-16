# Slugs'n'Shots backend üzleti logika

Frissítve: 2026-05-15.

Ez a dokumentum a backend üzleti szabályait foglalja össze. Nem frontend-specifikus képernyőterv, hanem a domain folyamatok és szerveroldali döntések rövid, karbantartható leírása.

## Szereplők

- **Látogató**: nincs bejelentkezve. Menüt olvashat. Anoním rendelés és fizetés külön későbbi modul.
- **Vendég**: regisztrált, bejelentkezett felhasználó. Asztalt foglalhat, asztalhoz csatlakozhat, rendelhet, fizethet, saját adatait exportálhatja vagy anonimizálást kérhet.
- **Asztal felelőse**: az a vendég, aki egy szabad asztalt GUID/QR kóddal lefoglalt.
- **Asztaltag**: jóváhagyással csatlakozott vendég. Rendelhet, ha a felelős nem tiltotta le a rendelési jogát.
- **Személyzet**: pultos, pincér, backoffice/admin. Rendeléseket kezel, törzsadatokat karbantart, bizonyos esetekben fizetettnek jelölhet tételeket.
- **Adminisztrátor**: magasabb jogosultságú személyzet. Zárt table session utólagos rendezését is elvégezheti auditált beavatkozásként.

## Regisztráció és profil

- Vendég regisztrációnál kötelező a 18+ elfogadás: `is_over_18`.
- Opcionális compliance/profil mezők: `birth_date`, `phone`, `address`.
- Az e-mail megerősítés külön folyamat.
- A profiladatok közül a vendég saját nevét módosíthatja az általános profil endpointon keresztül; profilképet külön feltöltés/törlés endpoint kezel.
- A profilkép feltöltési méretkorlátja `config/guests.php` alatt konfigurálható.
- Jelszómódosításkor a jelenlegi jelszó ellenőrzése kötelező.

## Menü és italok

- A publikus vendég menü csak aktív, nem törölt italokat ad vissza.
- A menü lokalizált mezőket használ (`name`, `description`, `unit`), a belső nyelvi oszlopok rejtve maradnak.
- A gyors rendeléshez létezik `GET /api/guest/recent-drinks?limit=10`.
- A recent drinks funkció célja nem az összes korábbi rendelés megőrzése, hanem egy minimalizált, személyhez kötött preferencia-snapshot.

## Asztalok és table session

- Az asztalokat a személyzet/admin hozza létre törzsadatként.
- Minden asztalnak van GUID-szerű kódja, amely QR-kódként is használható.
- Egy asztal foglalásakor nyitott `table_session` jön létre.
- Egy asztal akkor foglalható, ha aktív és nincs nyitott sessionje.
- Egy vendég egyszerre legfeljebb egy nyitott asztal felelőse lehet.
- A lezárt session után ugyanaz az asztal újra foglalható.
- Felelős csak akkor zárhatja az asztalt, ha nincs függő/fizetetlen rendelési tétel.

## Asztaltagság

- Bejelentkezett vendég GUID/QR alapján csatlakozási kérelmet hozhat létre egy foglalt asztalhoz.
- A felelős jóváhagyhatja vagy elutasíthatja a pending kérelmeket.
- A felelős eltávolíthat tagot.
- A felelős külön tilthatja/engedélyezheti egy tag rendelési jogát.
- A rendelési jog tiltása csak rendelésleadásra vonatkozik; tartozás rendezését nem tiltja.
- Látogató nem lehet asztaltag.

## Rendelés

- A jelenlegi backend üzleti szabály szerint vendég csak table sessionhöz kötötten adhat le utólag fizetős rendelést.
- A rendelés tételszintű fizetettséggel dolgozik: `order_details.payment_status`.
- Rendelésállapotok:
  - `open`: leadva, még nincs készítés alatt.
  - `preparing`: pultos/készítő dolgozik rajta.
  - `ready`: elkészült, felszolgálható.
  - `served`: felszolgált.
  - `cancelled`: törölt.
- Rendelésleadáskor a rendszer validálja, hogy a választott ital-egység létezik.
- Promóció/kedvezmény jelenleg nincs aktív kalkulációban: `promo_id = null`, `discount = 0`.
- Staff rendelésrögzítésnél vendéghez és opcionálisan table sessionhöz lehet rendelést rögzíteni. Ha table session meg van adva, nyitott és jogosult session kell.

## Fogyasztási limit

- A table sessionhöz tartozhat owner által beállított limit.
- A table sessionhöz tartozhat owner által beállított személyenkénti pending limit is, amely minden résztvevőre azonosan vonatkozik, beleértve az ownert.
- A staff/admin oldalon lehet alapértelmezett vagy session szintű limit-felülírás.
- Ha owner limit és staff limit is van, az alacsonyabb effektív limit érvényes.
- A `null` vagy `0` érték az adott oldalon limit nélküliséget jelent.
- Az asztalszintű limit a függő/fizetetlen asztaltételek összegére vonatkozik.
- A személyenkénti limit az adott rendelő vendég függő/fizetetlen tételeire vonatkozik.
- Ha egy új rendelés túllépné az effektív asztalszintű vagy személyenkénti limitet, a backend `409` választ ad.

## Fizetés és nyugta

- Fizetés rendelési tétel szinten történik.
- Vendég saját pending tételeit fizetheti.
- Asztaltársaságban owner vagy approved member fizethet a saját aktuális table session pending tételeiből.
- Záró fizetést csak az asztal felelőse indíthat; ez minden fennmaradó pending tételt tartalmaz.
- Sikertelen vagy félbehagyott fizetés nem állítja paid státuszra a rendelési tételeket.
- Sikeres fizetéskor:
  - létrejön `payment_attempt`,
  - létrejön `receipt`,
  - az érintett `order_details` sorok `payment_status=paid` értékre váltanak,
  - a tételek `receipt_id` kapcsolatot kapnak,
  - `payment_events` audit események jönnek létre.
- Staff/admin `admin_marked_paid` módszerrel fizetettnek jelölhet tételeket.
- Zárt table sessionhöz tartozó kivételes utólagos fizetettnek jelölést csak admin végezhet.

## Számviteli snapshot

- A nyugtához kapcsolódó issuer/customer/accounting mezők bizonylati snapshotként szolgálnak.
- Ezek nem request logok és nem technikai audit logok.
- GDPR anonimizálás vagy retention leválasztás nem írja át ezeket, mert számviteli megőrzési célból maradhatnak.
- A `receipts.guest_id` személyes kapcsolat leválasztható, miközben a bizonylati snapshot megmarad.

## GDPR és adatkezelés

- Vendég kérheti saját fiókja anonimizálását.
- Anonimizálás tiltott, ha:
  - a vendég nyitott asztal felelőse,
  - pending vagy approved tagsága van nyitott asztalnál,
  - van fizetésre váró rendelési tétele,
  - van aktív rendelése,
  - a fiók már anonimizált.
- Anonimizáláskor a vendég profil PII mezői törlődnek vagy maszkolódnak.
- A saját feltöltött profilkép adatbázis-linkje és storage fájlja is törlődik anonimizáláskor.
- Rendelési, fizetési és nyugta rekordok üzleti/számviteli okból megmaradnak, de a vendégre mutató személyes linkek leválasztódnak.
- A GDPR audit események megmaradnak, PII helyett maszkolt azonosítóval.
- A vendég saját adat exportot kérhet: `GET /api/guest/me/export`.
- Staff/admin vendégtörléskor nem sima soft delete történik, hanem ugyanazokra az előfeltételekre épülő GDPR anonimizálási flow fut. Blokkoló aktív állapot esetén a backend `409` választ ad.

## Retention policy

- Régi, lezárt/fizetett operatív rendelési adatoknál a vendégkapcsolat nap alapú szabály szerint leválasztható.
- A retention nem fizikai törléssel indul, hanem személyes linkek nullázásával.
- A gyors rendeléshez szükséges utolsó italok külön minimalizált snapshotban maradnak meg aktív vendégeknél.
- Anonimizáláskor ez a recent-drinks snapshot is törlődik, mert személyes preferenciaadat.
- A retention részletes működését lásd: [GDPR retention policy](gdpr-retention-policy.md).

## Audit és naplózás

- `payment_events`: fizetési életciklus és admin fizetettnek jelölés eseményei.
- `gdpr_audit_events`: GDPR anonimizálási kísérletek, blokkolások és sikeres anonimizálás.
- `RequestLogger`: fejlesztői/technikai request/response log, konfigurálható érzékeny mező maszkolással.
- A request log nem helyettesíti az üzleti auditot.

## Jövőbeni modulok

- Anoním vendég azonnali fizetéses rendelés.
- GUID alapú anoním nyugta-visszakeresés.
- Nyugta PDF letöltés és e-mail újraküldés.
- Riportok és CSV export.
- Promóció/kedvezmény kalkuláció.
- Audit log admin megtekintő és tamper-evidence stratégia.
- Bulk tesztadat generálás fejlesztői/demo/load környezethez.
