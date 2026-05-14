# Backend TODOs

## Kifejezések:

- látogató: az oldalt anoním módon használó felhasználó.
- anoním vendég: belépés nélkül rendelő felhasználó.
- vendég: regisztrált és bejelentkezett látogató.
- anonimizált vendég: korábban regisztrált vendég, aki GDPR szerinti adattörlést kért, ezért személyes adatai törlésre vagy maszkolásra kerültek.
- szolgáltató: a rendszer üzemeltetője, a vendéglátóhely
- személyzet: a szolgáltató által megbízott felhasználók (pultos, pincér, adminisztrátor)
- adminisztrátor: a rendszer belső működéséért felelős személyzet.

## Látogatói jogosultságok

- A látogató csak az itallapot/menüt olvashatja.
- A látogató rendelést csak azonnali fizetéssel adhat le.
- A látogató nem lehet asztal tagja, asztalt nem foglalhat, és asztalhoz nem csatlakozhat.

Rövid összefoglaló: a backend stabil alapot ad az autentikációhoz, az itallaphoz, az alap rendelésleadáshoz és a személyzeti rendelésfolyamhoz. A dolgozatban szereplő teljes üzleti képhez viszont több nagyobb modul még hiányzik vagy csak részben létezik.

## Magas prioritás

- Fizetés és nyugtakészítés API: a `ReceiptController` létezik, de nincs route-ra kötött, rendelési tételekből dolgozó fizetési folyamat.
- Promóciók tényleges kalkulációja: rendelésleadáskor jelenleg nincs aktív promóciókeresés, kedvezményszámítás vagy több promóció ütközésének kezelése.
- Asztaltársaság / aktív asztal kezelés: külön asztal- vagy csoport-session modell kellene a foglaló, tagok, nyitott tételek és lezárás követhető kezelésére.
- Szerveroldali rendelés- és fizetési jogosultságok: pontosítani kell, ki vehet fel rendelést, ki fizethet tételeket, ki zárhat asztalt, és milyen állapotból milyen állapotba lehet lépni.

### Asztalkezelés

- Az adminisztrátorok előre regisztrálják a helyeket, fizikai asztalokat és asztalcsoportokat. Ezek egyedi névvel és GUID-szerű foglalási kóddal rendelkeznek.
- A vendég az asztal GUID-szerű kódját olvassa be vagy adja meg. Ha az asztal még nem foglalt, ezzel lefoglalja, és ő válik az asztal felelősévé.
- Egyszerre egy asztal egy vendéghez lehet felelősként rendelve.
- Újabb vendég az asztal QR kódjának beolvasásával kérhet csatlakozást az asztalhoz. Az asztal vendégei közé csak az asztal felelősének jóváhagyása után kerülhet be.
- A jóváhagyott vendég is tud rendelést leadni az asztalhoz. A vendégek és a felelős is láthatja az asztal leadott és függő rendeléseit, és fizethetnek egy vagy több tételt.
- Az asztal felelőse megtilthatja az asztalhoz rendelt (megadott) vendégeknek a rendelést.

## Implementáció előtt tisztázandó folyamatok

Ezeknél a feladatoknál a technikai irány látszik, de az üzleti folyamatot még pontosítani kell. Addig nem érdemes végleges adatmodellt vagy API-t rögzíteni, amíg az alábbi kérdésekre nincs döntés.

### Fizetés és nyugtakészítés

- Döntés: a nyugta létrejöhet rendeléskor, ilyenkor a rendelés rögtön fizetettnek számít.
- Döntés: a nyugta létrejöhet később is, amikor a vendég egy vagy több rendelési tételt fizet, például távozáskor.
- Döntés: asztal csak akkor zárható, ha minden hozzá tartozó rendelési tétel fizetett; az asztal zárása az asztal felelősének feladata.
- Döntés: csak asztalhoz kötött rendelés lehet utólag fizetős; minden asztal nélküli rendelés azonnali fizetéses.
- Döntés: saját rendelésénél fizetést indíthat a látogató és a vendég.
- Döntés: asztaltársaságnál bármelyik asztaltag indíthat fizetést az asztal tételeire.
- Döntés: az adminisztrátor bárkinek a rendelését fizetettnek jelölheti.
- Döntés: saját rendelés fizetésekor a fizető a saját rendeléseiből kijelölt, függőben lévő tételeket fizetheti ki.
- Döntés: asztaltársaságnál a fizető az asztal nyitott/függő tételei közül bármennyit kifizethet.
- Döntés: a záró fizetés az asztal összes fennmaradó függő tételét tartalmazza.
- Döntés: a félbehagyott vagy sikertelen fizetés kapjon saját státuszt, de az érintett rendelési tételek maradjanak függőben.
- Döntés: fizetést visszavonni nem lehet.
- Döntés: a nyugta alapértelmezetten az alkalmazásban jelenik meg, innen letölthető.
- Döntés: a nyugta e-mailben is kérhető, illetve újraküldhető.
- Döntés: sztornó kérelmezése az alkalmazáson kívül, szóban történik.
- Döntés: megőrzendő auditadatok: fizető személy, fizetést rögzítő személyzet, időpont, fizetési mód, fizetett tételek, kedvezmények; ha személyzet kezdeményezte a fizetést, akkor a személyzet tagjának neve is.

### Asztaltársaság és aktív asztal

- Döntés: asztalt csak adminisztrátor hoz létre előre, a vendég a GUID-szerű asztalkód beolvasásával vagy megadásával foglalhatja le, ha még szabad.
- Döntés: további vendég az asztal QR kódjának beolvasásával és az asztal felelősének jóváhagyásával kerülhet az asztalhoz.
- Döntés: látogató, vagyis be nem jelentkezett felhasználó, nem lehet asztal tagja.
- Döntés: Csak felelős távolíthat el tagot, ilyenkor az eltávolított tag fogyasztásáért legkésőbb asztalzárásnál a felelős a rendezéséért, illetve az adminisztrátor, az adminisztrátor fizetettnek veheti a fogyasztását, vagy a felelősnek kell rendeznie.
- Döntés: vendég önként nem távozhat asztaltól, ha az asztalhoz kapcsolódóan van függő vagy fizetetlen rendelése.
- Döntés: az asztal felelőse zárhatja az asztalt, de csak akkor, ha minden asztalhoz tartozó tétel fizetett.
- Döntés: Az asztal felelőse felelős a nyitott tételek rendezéséért, legkésőbb az asztal zárásánál.

### Rendelés- és fizetési jogosultságok

- Döntés: pultosok és pincérek rendeléseket rendelhetnek vendéghez, vendégen keresztül asztalhoz, státuszt állíthatnak, illetve fizetettnek jelölhetnek rendelést.
- Döntés: pultosok és pincérek ideiglenesen lezárhatnak (inaktiválhatnak), illetve megnyithatnak inaktivált asztalt.
- Döntés: vendég csak várakozó állapotig módosíthat vagy törölhet saját rendelést.
- Döntés: pultos bármilyen nyitott állapotú rendelést töröltnek jelölhet.
- Döntés: adminisztrátor zárt rendelést is sztornózhat; a sztornó kérelmezése az alkalmazáson kívül, szóban történik.
- Döntés: a sztornó kérelmezése és egyeztetése szóban történik: a vendég szól a pincérnek vagy pultosnak, és a személyzettel rendezik.
- Döntés: pultos által kezelhető rendelésállapotok: nyitott, készítés alatt, felszolgálható, törölt.
- Döntés: pincér által kezelhető rendelésállapotok: nyitott, felszolgált, törölt.
- Döntés: fizetést saját rendelésnél a látogató és a vendég, asztaltársaságnál bármelyik asztaltag indíthat; adminisztrátor bárkinek a rendelését fizetettnek jelölheti.
- Döntés: fizetést visszavonni nem lehet.
- Döntés: alkalmazáson belüli fizetés-visszavonás nincs; a sztornó kérelmezése alkalmazáson kívüli folyamat.

### Promóciók és kedvezmények

- Döntés: Egyszerre csak egy promóció érvényesülhet ugyanarra a tételre.
- Döntés: "Egyet fizet kettőt kap" típusú promóciónál az ingyenes tétel 0 forintos egységáron jelenik meg a számlán.
- Döntés: A promóciót a rendelés véglegesítésekor kell szerveroldalon kalkulálni.
- Döntés: Audit célból el kell tárolni legalább a promóció azonosítóját, az eredeti egységárat, a kedvezmény százalékát és a kedvezmény összegét.

### Vendég törlés, inaktiválás és anonimizálás

- Döntés: vendég törlése/inaktiválása nem engedhető, ha aktív asztaltagsága, nyitott rendelése vagy ki nem fizetett tétele van.
- Döntés: adatokat üzleti okból nem törlünk fizikailag; végleges fióklezárás után a személyes adatokat anonimizálni kell.
- Döntés: az alkalmazásban legyen opció a vendég számára a saját fiókja anonimizálásának kezdeményezésére.
- Döntés: anonimizálás csak akkor indítható, ha a vendégnek nincs tartozása és nem tagja asztalnak.
- Döntés: az anoním vendég belépés nélkül rendel, ezért nála a nyugtán nem szerepel regisztrált vendégprofilhoz tartozó név.
- Döntés: a GDPR szerint anonimizált vendég korábban regisztrált felhasználó volt; nála a rendelési és nyugta előzményekben a személyes azonosító adatokat törölni vagy maszkolni kell.
- Döntés: inaktivált fiók adminisztrátori segítséggel visszaállítható lehet, de anonimizált fiók már nem állítható vissza.

### Riportok és lekérdezések

- Döntés: a dokumentációban felsorolt riportok fejlesztési irányként szerepelnek: napi fogyás, várakozó rendelések, átlagfogyasztás, népszerű/fogyott italok, erős/gyenge időszakok, promóciók, átfutási idők, rendelési gyakoriság, alkalmazotti teljesítmény és árváltozás.
- Döntés: szűrők: dátumtartomány, kategória, alkalmazott, fizetési mód, promóció
- Döntés: riportokat backoffice és adminisztrátor szerepkörök láthatnak.
- Döntés: Kell export CSV formátumban, a szerveren védett mappában adminisztrátorok láthatják csak. Az elkészült riportok egy táblába kerüljenek (checksum-mal) de a riport fájlok a könyvtárstruktúrában.
- Döntés: Milyen definíció alapján számoljuk a bevételt és fogyást: rendelésleadás, elkészítés, felszolgálás vagy fizetés időpontja szerint? A rendelés leadás pillanatában, a törölt rendelés akkor állíthatja vissza a készletet, ha még várakozik. Ha készítés alatt van, akkor kvázi hulladék lesz.

## Közepes prioritás

- Explicit rendelésállapot vagy állapotgép: a pusztán időbélyegekből levezetett állapot könnyen érvénytelen kombinációkhoz vezethet. - Lehet státusza, de csak a folyamatban előre, kivéve a törlés/stornó.
- Staff oldali rendelésfelvétel tételekkel: a pultos/pincér által vendég helyett rögzített rendeléshez route, validáció és teszt kell.
- Riportok és lekérdezések: napi fogyás, népszerű italok, bevétel, alkalmazotti teljesítmény és átfutási idők végpontjai hiányoznak.
- Vendég törlés / inaktiválás / GDPR szerinti anonimizálás szabályai: a jelenlegi soft delete mellé üzleti feltételek és személyesadat-kezelési folyamat kell. Személyes adatoknál a nevek első betűi, az emailcím azonosítórészének (@ előtti rész) első és utolsó karaktere maradhat maszkolt formában, pl. `e...m@example.com`.
- Gyors rendelés támogatása: kedvencek helyett egyelőre elegendő lehet az utolsó X rendelt ital megjelenítése.

## Alacsony prioritás

- OpenAPI és kézi API-listák szinkronban tartása: ahol lehet, a generált OpenAPI dokumentáció legyen az elsődleges forrás. A bemenő paramétereknél figyelje a validált/használt paramétereket, ahol nem megállapítható, dokumentálja, hogy ott beavatkozást igényel, illetve a válasznál is törekedjen a teljességre
- Import parancsok robusztusabbá tétele: validációs összefoglaló, hibás sorok jelentése, opcionális dry-run mód.
- Táblaszintű és üzleti megszorítások szétválasztása a dokumentációban: ami tranzakciós üzleti szabály, ne DB constraintként legyen leírva.
- Dockeres fejlesztői parancsok következetes dokumentálása a sima helyi PHP parancsok helyett.
- Unit tesztek megírása az OpenAPI-nál megírt paraméter és válasz szabályok szerint működni

## Jövőbeni fejlesztések

- eldönteni, hogy az itallap gyorsítótárazása hogyan történjen:
  - kliens oldalon - az összes nyelven
  - kliens oldalon - csak az aktív nyelven
  - szerver oldalon - előre letárolt szerverválasszal, nyelvenként
  - szerver oldalon - cache eljárással tárolt (pl redis), nyelvenként
