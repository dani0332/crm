# Validation Review Rules

## Form Request Classes

All validation must live in Form Request classes, not controllers.

Flag: `$request->validate([...])` inside any controller method.
Flag: any controller that type-hints `Request` instead of a named Form Request when it performs validation.

## `$request->validated()` Only

Flag any model `create()` or `update()` call that passes `$request->all()`, `$request->input()`, `$request->only()`, or a manually assembled array instead of `$request->validated()`.

## Array Rule Notation

New code must use array notation for rules, not pipe strings.

```php
// Bad (pipe string — legacy)
'email' => 'required|email|max:255',

// Good (array)
'email' => ['required', 'email', 'max:255'],
```

Flag only if the existing file already uses array notation (follow existing convention per Consistency First).

## Conditional Rules

Flag duplicated validation rules that differ only by a condition. Suggest `Rule::when()`.

```php
// Good
'card_number' => Rule::when($request->payment === 'card', ['required', 'digits:16']),
```

## `after()` for Cross-Field Validation

Flag use of `withValidator()` for adding conditional errors. Use `after()` instead (cleaner API).

## Authorization in Form Requests

Every Form Request `authorize()` must perform a real check. Flag trivially `return true;` on requests that mutate protected resources.
