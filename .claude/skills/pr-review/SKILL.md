---
name: pr-review
description: 'Review code pull requests in Laravel projects applying best practices. Activate when the user asks to review a PR, review a branch, check code quality, or audit pending changes. Covers: security, N+1 queries, validation, authorization, test coverage, queue/job patterns, error handling, migrations, DRY/SRP violations, static magic strings, hardcoded date formats, and config-driven values. Works by diffing the current branch against main/develop and systematically checking each changed file.'
license: MIT
metadata:
  author: laravel
---

# PR Review

A structured, opinionated review process for Laravel pull requests. Produces actionable findings grouped by severity.

## When to Activate

Activate this skill whenever the user asks to:

- Review a PR or branch
- Check code quality or audit changes
- Run a pre-merge review
- Find issues before opening a pull request

## Review Process

Always use a sub-agent to read rule files and explore this skill's content.

### Step 1 — Gather the diff

```bash
# Get all changed files on this branch vs. the base branch
git diff origin/develop...HEAD --name-only

# Full diff with context
git diff origin/develop...HEAD
```

If the user specifies a PR number, use `gh pr diff <number>` instead.

### Step 2 — Select relevant rule files

Read the rule files that match the changed file types:

| Changed files               | Read rule files                                                                   |
| --------------------------- | --------------------------------------------------------------------------------- |
| Models, migrations          | `rules/eloquent.md`, `rules/migrations.md`, `rules/security.md`                   |
| Controllers, Form Requests  | `rules/controllers.md`, `rules/security.md`, `rules/validation.md`                |
| Jobs, queues                | `rules/jobs.md`                                                                   |
| Routes                      | `rules/controllers.md`                                                            |
| Tests                       | `rules/tests.md`                                                                  |
| Services, Actions           | `rules/architecture.md`                                                           |
| Config, env values, formats | `rules/config.md`                                                                 |
| Any PHP                     | `rules/security.md`, `rules/style.md`, `rules/architecture.md`, `rules/config.md` |

### Step 3 — Apply rules to each changed file

For each file in the diff, check it against every applicable rule. Do NOT skip files.

### Step 4 — Report findings

Group findings by severity. Use the format defined in `rules/output-format.md`.

## Quick Reference

### Critical (must fix before merge)

- SQL injection via raw queries with user input
- Missing authorization (`Gate::authorize`, policy, or Form Request `authorize()`)
- `$guarded = []` on models that accept user input
- Secrets accessed via `env()` in application code (not config files)
- Missing `$fillable` on new models
- Migrations that modify production data without a rollback path

### High (should fix before merge)

- N+1 queries — missing `with()` eager loading
- `$request->all()` or `$request->input()` instead of `$request->validated()`
- Inline validation in controllers instead of Form Request classes
- Missing tests for new public-facing behavior
- Jobs without a `failed()` method
- `retry_after` shorter than job `timeout`
- `env()` called for a secret/credential outside a config file
- DRY violation — duplicated business-rule logic across multiple files
- SRP violation — class mixing persistence, business logic, and notification concerns
- Hardcoded date/datetime format string used in more than one file

### Medium (fix soon)

- `env()` called outside config files for non-secret settings
- Hardcoded date format appearing in a single file (must move to config)
- Magic status/type string literals that should be an Enum
- Hardcoded pagination limits, cache TTLs, or expiry durations (use config)
- Hardcoded timezone string instead of `config('app.timezone')`
- Controllers with methods over 10 lines of logic
- Missing return type hints or parameter types
- Scoped bindings missing for nested resource routes
- Global scopes added without a comment explaining their existence
- SRP violation — method doing more than one logical step

### Low (consider fixing)

- Non-standard naming (snake_case methods, PascalCase classes, etc.)
- Comments that describe _what_ instead of _why_
- `assertStatus(200)` instead of `assertSuccessful()` in tests
- Hardcoded table names instead of `(new Model)->getTable()`
- Repeated display string that should move to a lang file
- Queue/channel name as a string literal at the call site instead of a constant

## How to Apply

1. Run the diff commands in Step 1 to see what changed.
2. Select the relevant rule files from Step 2.
3. Read each rule file and check each changed file against it.
4. Report all findings using `rules/output-format.md`.
5. Always end with a summary verdict: **Approve**, **Approve with suggestions**, or **Request changes**.
