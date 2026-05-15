# Slugs'n'Shots Backend

Laravel alapú backend a Slugs'n'Shots vendéglátóhelyi rendelés-, asztal-, fizetés- és adatkezelési folyamataihoz.

A projekt célja egy olyan API biztosítása, amely támogatja a vendégoldali itallapot és rendelést, az asztaltársaság kezelését, a személyzeti rendelésfolyamatot, a fizetést/nyugtázást, valamint a GDPR szerinti adatkezelési műveleteket.

## Fő képességek

- Vendég és személyzeti autentikáció JWT alapon.
- Publikus/vendég itallap lokalizált névvel, leírással és egységekkel.
- Vendég regisztráció e-mail megerősítéssel és 18+ elfogadással.
- Asztaltörzs, GUID/QR alapú asztalfoglalás és table session kezelés.
- Asztaltagság: csatlakozási kérés, jóváhagyás, elutasítás, rendelési jog tiltása/engedése.
- Table session fogyasztási limitek vendég és staff/admin oldalról.
- Vendég és staff rendelésleadás table sessionhöz kötötten.
- Rendelési státuszfolyamat pultos/pincér munkához.
- Tételszintű fizetési állapot (`pending`, `paid`).
- Vendég saját fizetés, asztal részfizetés és záró fizetés.
- Staff/admin fizetettnek jelölés auditált beavatkozásként.
- Nyugta és számviteli snapshot mezők.
- Payment attempt és payment event audit trail.
- GDPR anonimizálás előfeltétel-ellenőrzéssel.
- Saját vendég adat export strukturált JSON válasszal.
- Retention command régi operatív személyes kapcsolatok leválasztására.
- Minimalizált recent-drinks snapshot a gyors rendelés támogatására.
- OpenAPI dokumentáció swagger-php / L5 Swagger alapon.
- PHPUnit feature és unit tesztlefedettség a kritikus üzleti folyamatokra.

## GDPR és adatvédelem

A backend több, adatvédelemhez kapcsolódó funkciót tartalmaz:

- vendég saját fiók anonimizálása,
- anonimizálás tiltása aktív asztal, tagság, rendelés vagy fizetetlen tétel esetén,
- személyes profiladatok törlése vagy maszkolása,
- rendelési/fizetési/nyugta rekordok üzleti megőrzése személyes linkek leválasztásával,
- GDPR audit események,
- request logger érzékeny mező maszkolással,
- saját adat export,
- retention policy és `gdpr:retention-prune` Artisan command.

## Dokumentáció

- [Üzleti logika](docs/business-logic.md)
- [GDPR retention policy](docs/gdpr-retention-policy.md)
- [Vendég adatkezelési összefoglaló](docs/guest-data-protection-summary.md)
- [Wiki oldal](https://github.com/slug-n-shots/backend/wiki)

## Technológia

- Laravel 10
- PHP 8.2+
- MySQL
- Redis
- PHPUnit 10
- JWT auth (`tymon/jwt-auth`)
- OpenAPI / swagger-php / L5 Swagger

## Fejlesztői parancsok

PHP parancsokat Docker PHP konténerben érdemes futtatni:

```bash
docker compose exec php php artisan migrate:status
docker compose exec php php artisan route:list
docker compose exec php vendor/bin/phpunit
docker compose exec php php artisan l5-swagger:generate
```

GDPR retention dry-run:

```bash
docker compose exec php php artisan gdpr:retention-prune --dry-run
```

## API dokumentáció

Fejlesztői környezetben az OpenAPI/Swagger dokumentáció a L5 Swagger beállításai szerint érhető el, tipikusan:

```text
/api/documentation
```

OpenAPI újragenerálás:

```bash
docker compose exec php php artisan l5-swagger:generate
```

## Tesztek

Teljes tesztcsomag:

```bash
docker compose exec php vendor/bin/phpunit
```

Szűkített példa:

```bash
docker compose exec php vendor/bin/phpunit --filter=GuestAnonymizationTest
docker compose exec php vendor/bin/phpunit --filter=GdprRetentionPruneTest
```
