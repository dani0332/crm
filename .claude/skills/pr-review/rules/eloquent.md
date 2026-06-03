# Eloquent & Database Review Rules

## N+1 Queries

This is the most common and impactful issue. Look for any loop that calls a relationship on a model that was not eager-loaded.

```php
// Bad — N+1
$orders = Order::all();
foreach ($orders as $order) {
    echo $order->user->name; // query per iteration
}

// Good
$orders = Order::with('user')->get();
```

Also flag controllers or services that return collections to views/responses without eager loading obvious relationships.

## Select Only Needed Columns

Flag `Model::all()` or queries without `->select(...)` when the model has many columns or when only a subset is used downstream. Use `->select(['id', 'name'])`.

## Large Dataset Iteration

Flag `->get()` on potentially unbounded result sets in commands, jobs, or exports. Suggest `->chunk()`, `->chunkById()`, or `->cursor()`.

## Relationship Return Types

Every relationship method must have a return type hint.

```php
// Bad
public function user()

// Good
public function user(): BelongsTo
```

## Casts

Date/timestamp columns must be cast. Sensitive fields (tokens, keys) must use `encrypted` cast. Boolean columns must be cast to `bool`.

## Hardcoded Table Names

Flag any string table name in queries. Use Eloquent models or `(new Model)->getTable()`.

## Local Scopes

Flag repeated `->where('status', 'active')` style constraints duplicated across the codebase. Suggest a local scope.

## `withCount` Over Loading Relations

Flag code that loads a relation only to call `->count()` on it. Use `withCount()` instead.
