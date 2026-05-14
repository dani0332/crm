# Migration Review Rules

## Never Modify Existing Migrations

Flag any changes to a migration file that already exists on the `develop` or `main` branch. Changes to existing migrations break deployments. New migrations must be added instead.

## Foreign Key Constraints

Every foreign key column must use `->constrained()`.

```php
// Bad
$table->unsignedBigInteger('user_id');

// Good
$table->foreignId('user_id')->constrained();
```

## Indexes

Flag columns used in `WHERE`, `ORDER BY`, or `JOIN` clauses that don't have a corresponding index in the migration.

Common misses: `status`, `type`, `created_at` on large tables, composite sort columns.

## Reversible `down()` Method

Every migration must have a working `down()` that reverses the `up()`. Flag:
- Empty `down()` methods
- `down()` that calls `Schema::drop()` on a table it didn't create
- Missing column drops matching added columns

Exception: intentionally destructive migrations (e.g., dropping a column with data) — these should have a comment explaining why `down()` is not implemented.

## One Concern Per Migration

Flag migrations that mix schema changes (DDL) with data backfills (DML). Separate them into two migrations.

## `nullable()` on New Non-Nullable Columns

Adding a `NOT NULL` column to an existing table without a default will fail on non-empty tables. Flag new non-nullable, no-default columns added to existing tables — they need a `default()` or a two-step migration (add nullable, backfill, add constraint).

## Column Defaults Mirror Model Attributes

If a migration sets a column default, the model should mirror it in `$attributes`. Flag mismatches.
