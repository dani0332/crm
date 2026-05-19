# Security Review Rules

## Mass Assignment

Every model that accepts user input must define `$fillable`. Flag any new model missing it, or any model using `$guarded = []`.

```php
// Bad
protected $guarded = [];

// Good
protected $fillable = ['name', 'email'];
```

## Authorization

Every controller action that reads or mutates a resource must authorize. Check for `Gate::authorize()`, `$this->authorize()`, or a Form Request with a non-trivially-true `authorize()` method.

Missing authorization on `store`, `update`, `destroy` is a Critical finding.

## SQL Injection

Flag any `DB::statement`, `DB::select`, `whereRaw`, `orderByRaw`, or `selectRaw` call that interpolates a variable directly into the string instead of using bindings.

```php
// Bad — Critical
DB::select("SELECT * FROM users WHERE name = '{$request->name}'");

// Good
DB::select('SELECT * FROM users WHERE name = ?', [$request->name]);
```

## Secrets in Code

Flag any `env('...')` call outside of `config/` files. Application code must use `config('...')` only.

## Sensitive Fields

New model attributes that store tokens, keys, or passwords must use the `encrypted` cast and be listed in `$hidden`.

## File Uploads

Any file upload must validate `mimes` (or `mimetypes`) and `max` size. Never trust client-provided filenames — use `store()` which generates a random name.

## Output Escaping

In Blade: `{{ }}` always. Flag any `{!! !!}` on user-supplied data as Critical.
