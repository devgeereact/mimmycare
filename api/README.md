# MimmyCare API (PHP/MySQL)

**Port (local dev):** 8041
**Deploy target:** `/home/devgeereact/mimmycare.com/` — the **domain root**, not an
`api/` subdirectory. There is no `api/` directory on the server; `api.php` sits
alongside `index.html`. (cPanel **premium17**, UK datacenter — migrated from
premium152/US on 2026-07-21.)

The API source is **not in this repository**. Only this README and
`config.example.php` are tracked under `api/`.

## Deployment — MANUAL (option A)

This API is **NOT** part of the GitHub Actions FTP auto-deploy. The workflow
(`.github/workflows/deploy.yml`) syncs `landing/` and `app/` only, so CI can
never clobber server-side config or DB credentials.

Deploy API changes by hand. **`ssh cpanel` does not work** — the account's login
shell is disabled on the UK server (key auth is fine, shell is not). Use SFTP:

```bash
lftp sftp://cpanel -e 'put api.php -o /home/devgeereact/mimmycare.com/api.php; quit'
```

or cPanel File Manager. `rsync` will not work (it needs a remote shell).

## Secrets

- Real DB creds live in `config.php` **on the server only** — never committed.
- `config.example.php` is the tracked template (no values).
- `.htaccess` lives on the server; keep a copy locally if needed but do not
  commit live rewrite/security rules that reveal structure.

## Files on server (verified 2026-07-21)

All at the domain root `/home/devgeereact/mimmycare.com/`:

- `api.php` — **still the unmodified boilerplate template.** Queries a table
  called `items` that does not exist in `devgeereact_mimmycare`, so `GET`
  returns HTTP 500. Nothing in `landing/` or `app/` calls it yet, so this is
  dormant scaffolding, not a live outage. Adapt it to real tables (or delete
  it) before wiring the frontend to it.
- `config.php` — live DB credentials. Fixed 2026-07-21: it had been copied from
  `config.example.php` (an array-return template) with the `return [` / `];`
  wrapper dropped, leaving orphaned `'KEY' => 'value'` lines and a fatal parse
  error. Now uses plain `$host` / `$dbuser` / `$dbpass` / `$dbname` variables,
  which is what its own `new mysqli(...)` call expects. Broken original kept as
  `config.php.bak-20260721`.
  > ⚠️ Note the format mismatch: `config.example.php` returns an **array**, but
  > the live `config.php` defines **variables** and opens the connection itself.
  > Do not copy the example verbatim again — it will not work.
- `.htaccess` — SPA fallback for the landing site. Was named `htaccess` (no
  leading dot) so Apache ignored it entirely; renamed 2026-07-21, which fixed
  deep links and page refreshes.
- `app/.htaccess` — added 2026-07-21, same rules with `RewriteBase /app/`, so
  app deep links (`/app/dashboard`) resolve instead of 404ing.
- `mimmycare.com/` — stray nested copy of the site from a bad FTP deploy path.
  Orphaned; safe to review and remove.
