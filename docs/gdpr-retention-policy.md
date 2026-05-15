# GDPR retention policy

Frissítve: 2026-05-15.

Ez a dokumentum a backend adatmegőrzési és személyesadat-leválasztási logikáját írja le. A cél az adattakarékosság: operatív célból már nem szükséges rendelési személyes kapcsolatokat ne tartsunk meg tovább a szükségesnél, miközben a számviteli és üzleti integritás megmarad.

## Alapelv

- Fizikai törlés helyett első körben személyes linkeket választunk le.
- A lezárt/fizetett operatív rendelési előzmény üzleti statisztikai és számviteli okból megmaradhat.
- A vendégprofilhoz való közvetlen kapcsolat csak addig marad, ameddig operatívan szükséges.
- A vendég kényelmi funkciójához szükséges "utolsó italok" külön minimalizált snapshotban él.

## Konfiguráció

Fájl: `config/gdpr.php`

Környezeti változók:

```env
GDPR_ORDER_PERSONAL_DATA_RETENTION_DAYS=30
GDPR_RECENT_DRINKS_PER_GUEST=10
```

- `GDPR_ORDER_PERSONAL_DATA_RETENTION_DAYS`: hány napig maradjon meg a régi operatív rendelés vendéghez kötve.
- `GDPR_RECENT_DRINKS_PER_GUEST`: vendégenként hány ital maradjon meg a gyors rendeléshez szükséges minimalizált snapshotban.

## Artisan command

Command:

```bash
docker compose exec php php artisan gdpr:retention-prune --dry-run
docker compose exec php php artisan gdpr:retention-prune
```

Opciók:

```bash
docker compose exec php php artisan gdpr:retention-prune --days=7 --dry-run
docker compose exec php php artisan gdpr:retention-prune --before=2026-05-15 --dry-run
```

Javasolt használat:

1. Először mindig `--dry-run`.
2. Ellenőrizni kell az érintett darabszámokat.
3. Zárási folyamatban vagy napi ütemezéssel fusson ténylegesen.
4. Éles környezetben scheduler előtt legalább egy kézi próbakör szükséges.

## Mit választ le a command?

`gdpr:retention-prune` a cutoff dátumnál régebbi, lezárt/fizetett operatív adatokat kezeli.

Leválasztott mezők:

- `orders.guest_id = null`
- `receipts.guest_id = null`
- `payment_attempts.guest_id = null`
- `payment_events.actor_guest_id = null`

Nem törli:

- `orders` rekordot,
- `order_details` rekordot,
- `receipts` rekordot,
- `payment_attempts` rekordot,
- `payment_events` rekordot,
- receipt accounting/customer snapshot mezőket.

## Mikor eligible egy rendelés?

Egy rendelés akkor retention-eligible, ha:

- van `guest_id`,
- státusza `served` vagy `cancelled`,
- `recorded_at` vagy ennek hiányában `created_at` régebbi a cutoffnál,
- nincs pending fizetési státuszú rendelési tétele.

Nem eligible:

- `open`,
- `preparing`,
- `ready`,
- bármely olyan rendelés, amelyhez még `payment_status=pending` tétel tartozik.

## Nyugták és számviteli adatok

A retention command csak a `receipts.guest_id` kapcsolatot nullázza. A bizonylati snapshot megmarad:

- issuer adatok,
- customer adatok,
- adószám,
- accounting gross total,
- accounting items,
- bookkeeping mezők.

Indok: ezek nem a profilkényelmi működéshez tartozó személyes linkek, hanem számviteli/bizonylati megőrzési adatok.

## Payment attempts és payment events

`payment_attempts.guest_id` akkor nullázható, ha:

- nem pending státuszú,
- `finished_at` vagy ennek hiányában `created_at` régebbi a cutoffnál.

`payment_events.actor_guest_id` akkor nullázható, ha:

- az event régebbi a cutoffnál,
- a kapcsolódó payment attempt nem pending.

Pending fizetési próbálkozásnál nem bontunk guest linket, mert operatív feldolgozás alatt állhat.

## Recent drinks snapshot

Tábla: `guest_recent_drinks`

Mezők:

- `guest_id`
- `drink_id`
- `last_ordered_at`
- `order_count`

Cél:

- A `GET /api/guest/recent-drinks?limit=10` funkció működjön akkor is, ha a régi rendelések személyes linkje retention miatt már levált.
- Ne kelljen a teljes régi rendelési előzményt vendéghez kötötten tartani csak a gyors rendelés miatt.

Adatminimalizálás:

- csak ital szintű preferencia marad,
- nincs régi rendelésazonosító,
- nincs rendelési tételazonosító,
- nincs ár/tábla/fizetés/receipt kapcsolat,
- vendégenként limitált elemszám.

Anonimizáláskor a recent-drinks snapshot törlődik, mert személyes preferenciaadat.

## Vendég anonimizálás és retention különbsége

Anonimizálás:

- vendég által indított fióklezárási/adattörlési folyamat,
- profil PII törlődik/maszkolódik,
- aktív tartozás/asztal/rendelés mellett tiltott,
- recent-drinks snapshot törlődik,
- kapcsolatok azonnal leválasztódnak.

Retention:

- üzemeltetői/időalapú adatminimalizálási folyamat,
- aktív vendég profilja megmarad,
- régi lezárt/fizetett operatív kapcsolatok nullázódnak,
- recent-drinks snapshot megmaradhat a kényelmi funkcióhoz.

## Rollback és adatvesztési kockázat

Migration rollback:

- a `guest_recent_drinks` tábla törlődik,
- ez a gyors rendeléshez szükséges minimalizált snapshot elvesztését jelenti,
- rendelési, fizetési és nyugta rekordot nem töröl.

Command rollback:

- a `gdpr:retention-prune` által nullázott `guest_id` kapcsolatok automatikusan nem állíthatók vissza,
- ezért éles futtatás előtt `--dry-run` kötelezően ajánlott,
- audit/backup stratégia nélkül a leválasztás üzletileg véglegesnek tekintendő.

## Ütemezés

MVP javaslat:

- kézi futtatás záráskor vagy admin üzemeltetői folyamatból,
- scheduler csak akkor, ha az üzleti nap zárása stabilan definiált.

Későbbi scheduler példa:

```php
$schedule->command('gdpr:retention-prune')->dailyAt('03:00');
```

Első éles scheduler előtt:

- legalább egy teljes `--dry-run`,
- darabszámok ellenőrzése,
- backup meglétének ellenőrzése,
- retention napok üzleti jóváhagyása.

## Tesztlefedettség

Fókuszteszt:

- `tests/Feature/Console/GdprRetentionPruneTest.php`

Lefedi:

- dry-run nem módosít adatot,
- régi served/paid rendelés személyes linkje leválik,
- nyugta accounting/customer snapshot megmarad,
- payment attempt/event guest link leválik,
- recent-drinks snapshot létrejön és működik,
- aktív vagy pending fizetésű rendelés linkje megmarad.

Kapcsolódó regressziós tesztek:

- `GuestAnonymizationTest`
- `GuestDataExportTest`
- `DrinkControllerTest`
- `OrderFlowTest`
