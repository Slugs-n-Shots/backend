# Backend AGENTS.md

## Backend context

This folder contains the Laravel backend application.

Common stack:
- Laravel
- PHP 8.2+
- MySQL
- Redis
- PHPUnit
- OpenAPI / swagger-php annotations where applicable

## Laravel working rules

- Follow existing Laravel conventions already used in this codebase.
- Keep controller methods thin.
- Prefer service classes for business logic when the surrounding code uses that pattern.
- Prefer FormRequest validation when the surrounding code uses FormRequest classes.
- Preserve existing API response structures.
- Do not change migrations that may already have run; add a new migration instead.
- Do not introduce new packages unless explicitly requested or clearly justified.

## Database and migrations

Before changing database-related code, identify:
- affected tables
- backward compatibility impact
- nullable/default value implications
- migration rollback behavior
- data migration risk

For risky schema changes, explain the risk before implementation.

## API and OpenAPI

When changing backend API behavior:
- check routes
- check request validation
- check response shape
- check OpenAPI/PHPDoc annotations
- check tests or add/update focused tests where appropriate

Do not document fields or responses that are not implemented by the code.

## Payment, wallet, and account logic

Payment, wallet, funder, wagering, winnings, PaySafe, ACH, and balance logic are high-risk areas.

For these changes:
- identify money movement direction
- preserve idempotency where present
- consider duplicate requests and retries
- consider partial failure behavior
- do not assume wagering and winnings accounts are interchangeable
- do not bypass existing authorization or validation

## Verification

Prefer narrow verification first:

- php artisan test --filter=<relevant test>
- vendor/bin/phpunit --filter=<relevant test>
- php artisan route:list, when route changes are made
- php artisan migrate:status, when migration assumptions matter

If checks cannot be run, explain why and provide manual verification steps.
