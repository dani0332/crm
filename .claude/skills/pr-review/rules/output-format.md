# Review Output Format

## Structure

Always produce a review in this exact structure:

```
## PR Review: <branch or PR title>

### Summary
<1–2 sentences on what the PR does and overall verdict>

---

### 🔴 Critical — Must Fix
<findings or "None">

### 🟠 High — Should Fix
<findings or "None">

### 🟡 Medium — Consider Fixing
<findings or "None">

### 🟢 Low / Suggestions
<findings or "None">

---

### Verdict
**<Approve | Approve with suggestions | Request changes>**
<one sentence reason>
```

## Finding Format

Each finding must include:

````
**[CATEGORY]** `path/to/File.php:line`
> What the issue is and why it matters.

```php
// Problematic code (if short enough to quote)
````

Fix: what to do instead.

```

## Categories

Use one of: `SECURITY`, `N+1`, `VALIDATION`, `AUTHORIZATION`, `TESTING`, `JOBS`, `MIGRATIONS`, `STYLE`, `ARCHITECTURE`, `PERFORMANCE`, `ERROR-HANDLING`

## Rules

- Quote the exact file path and line number when possible.
- Never invent findings — only report what is actually in the diff.
- If a file has no issues, do not mention it.
- If the PR only adds tests, skip categories that don't apply and say so.
- Keep fix suggestions concise — one or two lines of code is enough.
```
