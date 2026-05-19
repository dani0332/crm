# Controllers & Routing Review Rules

## Thin Controllers

Flag any controller method with more than ~10 lines of logic. Business logic belongs in Action or Service classes.

```php
// Bad — logic in controller
public function store(Request $request)
{
    $validated = $request->validate([...]);
    // ... 20 lines of business logic ...
}

// Good — delegate to an action
public function store(StoreOrderRequest $request, PlaceOrderAction $action)
{
    $order = $action->execute($request->validated());
    return redirect()->route('orders.show', $order);
}
```

## Validation in Controllers

Flag any `$request->validate([...])` inside a controller method. It must be in a Form Request class.

## Use `$request->validated()` Only

Flag any use of `$request->all()`, `$request->input()`, or `$request->only()` when populating a model. Only `$request->validated()` is acceptable after a Form Request.

## Implicit Route Model Binding

Flag manual `findOrFail($id)` calls when the route already has a typed parameter. Use route model binding instead.

```php
// Bad
public function show(int $id)
{
    $post = Post::findOrFail($id);
}

// Good
public function show(Post $post) { }
```

## Scoped Bindings

Flag nested resource routes (e.g., `/users/{user}/posts/{post}`) that don't use `->scopeBindings()`. Without it, the child model is not scoped to the parent.

## Resource Routes

Flag manually declared CRUD routes that could be replaced with `Route::resource()` or `Route::apiResource()`.

## Return Types

All controller methods must declare return types (`Response`, `RedirectResponse`, `JsonResponse`, etc.).
