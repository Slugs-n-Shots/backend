# Bulk tesztadat generáló modul terv

## Cél

Nagyobb, életszerű fejlesztői és demó adatbázis gyors előállítása úgy, hogy a rendelési, asztal-, fizetési, GDPR és retention folyamatok együtt is kipróbálhatók legyenek.

Nem cél éles adat migrálása vagy anonimizált éles adat visszatöltése.

## Javasolt parancs

```bash
docker compose exec php php artisan test-data:bulk --preset=demo
docker compose exec php php artisan test-data:bulk --preset=load --fresh
docker compose exec php php artisan test-data:bulk --guests=300 --orders=5000 --days=90
```

Státusz: az első backend implementáció elkészült `test-data:bulk` Artisan command néven. A parancs csak `local` és `testing` environmentben fut, támogatja a preseteket, a `--dry-run`, `--fresh`, `--force`, `--seed` és méret-felülíró opciókat.

Opciók:

- `--preset=small|demo|load`: előre definiált méretek.
- `--fresh`: opcionális adatbázis újrahúzás migrációval.
- `--guests`, `--employees`, `--tables`, `--orders`, `--days`: méret finomhangolás.
- `--seed=12345`: reprodukálható véletlen.
- `--with-gdpr-cases`: anonimizált, retention-eligible és blokkolt vendégek létrehozása.
- `--dry-run`: tervezett darabszámok kiírása adatírás nélkül.

## Modul felépítés

- `app/Console/Commands/BulkTestDataGenerate.php`
  - CLI opciók kezelése.
  - tranzakciók és progress bar.
  - preset validáció.
- `app/Services/TestData/BulkTestDataService.php`
  - teljes generálási folyamat koordinálása.
- `app/Services/TestData/Scenario/*`
  - külön scenario osztályok a jól olvasható üzleti esetekhez.
- `config/test_data.php`
  - presetek és alapértelmezett arányok.

## Presetek

`small`:

- 20 vendég
- 5 alkalmazott
- 8 asztal
- 100 rendelés 14 napra

`demo`:

- 120 vendég
- 10 alkalmazott
- 20 asztal
- 1500 rendelés 60 napra
- fizetett, függő, aktív és zárt asztalfolyamatok vegyesen

`load`:

- 1000 vendég
- 30 alkalmazott
- 60 asztal
- 50000 rendelés 365 napra
- retention és riport teljesítménypróbához

## Életszerű adateloszlás

- Rendelések időben csúcsosodjanak: péntek/szombat este több rendelés.
- Italválasztás súlyozott legyen: néhány népszerű ital sokszor, long tail ritkán.
- Rendelési tételszám tipikusan 1-4 tétel, ritkán nagyobb kör.
- Fizetési módok vegyesen: készpénz, kártya, admin által fizetettnek jelölt kivétel.
- Asztaloknál legyen:
  - nyitott owner-only session,
  - approved tagokkal működő session,
  - pending csatlakozási kérelem,
  - rendelésre tiltott tag,
  - fogyasztási limit közeli és limitet elérő állapot.

## GDPR scenario-k

Legyenek külön, név szerint felismerhető vendégek:

- `gdpr.clean@example.com`: anonimizálható, nincs blokkoló ok.
- `gdpr.pending-payment@example.com`: pending tétel miatt blokkolt.
- `gdpr.open-table-owner@example.com`: nyitott asztal owner miatt blokkolt.
- `gdpr.open-member@example.com`: aktív asztaltagság miatt blokkolt.
- `gdpr.retention-old@example.com`: régi served/paid rendelései retention-eligible állapotban.
- `gdpr.anonymized@example.com`: már anonimizált minta.

Ezekhez tartozzon adat exporttal ellenőrizhető:

- profiladat,
- rendelés,
- fizetés,
- nyugta,
- recent-drinks snapshot,
- GDPR audit esemény, ahol releváns.

## Biztonsági szabályok

- Alapból csak `local` és `testing` environmentben fusson.
- `production` környezetben azonnal álljon meg.
- `--fresh` külön megerősítést igényeljen interaktív módban, CI-ben pedig csak explicit `--force` mellett fusson.
- Jelszavak minden generált vendégnél és dolgozónál dokumentált fejlesztői jelszóra álljanak, például `Password1!`.

## Implementációs sorrend

1. `config/test_data.php` presetek.
2. Artisan command `--dry-run` és environment guard.
3. Törzsadat biztosítás: kategóriák, italok, drink unitok, alkalmazottak, asztalok.
4. Vendégek generálása compliance mezőkkel.
5. Rendelések és order detail sorok időbeli eloszlással.
6. Fizetés/nyugta/payment event generálás.
7. Table session és table member scenario-k.
8. GDPR/retention célállapotok.
9. Fókusztesztek: darabszám, ismert scenario vendégek, pending/paid arány, retention-eligible adatok.
