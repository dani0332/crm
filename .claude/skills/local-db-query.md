# Skill: Local DB Query Execution

## Context

This project connects to an **Azure MySQL** staging database via **Doppler** secrets.

- The local `.env` has `DB_HOST=mysql` (a Docker hostname) which does **not** resolve on this machine.
- The correct DB credentials live in Doppler config: `localenvs_fahad_uat`
- Database: `test_central_afia` on `az-mysql-insurancemarket-stgcentral db.mysql.database.azure.com`

## How to Run a Query

Always prefix commands with `doppler run --` so the correct Azure DB credentials are injected:

```bash
doppler run -- php artisan tinker --execute '<your PHP here>'
```

### Examples

**Fetch latest record from a table:**
```bash
doppler run -- php artisan tinker --execute 'echo json_encode(DB::table("car_quote_request")->latest("created_at")->first(), JSON_PRETTY_PRINT);'
```

**Count rows with a condition:**
```bash
doppler run -- php artisan tinker --execute 'echo DB::table("car_quote_request")->where("source", "EA_IMCRM")->count();'
```

**Use an Eloquent model:**
```bash
doppler run -- php artisan tinker --execute 'echo json_encode(\App\Models\CarQuote::where("source", "EA_IMCRM")->latest()->first(), JSON_PRETTY_PRINT);'
```

**Run a raw SELECT:**
```bash
doppler run -- php artisan tinker --execute 'print_r(DB::select("SELECT id, code, source FROM car_quote_request ORDER BY created_at DESC LIMIT 5"));'
```

## Rules

- Always use `doppler run --` — never run `php artisan tinker` bare, it will fail with a DNS error.
- Always use single quotes around the `--execute` argument to prevent shell expansion.
- Use double quotes inside PHP strings within the single-quoted argument.
- The `database-query` Boost MCP tool does NOT work here because it reads `.env` directly (which has the wrong Docker host). Use tinker via Doppler instead.
