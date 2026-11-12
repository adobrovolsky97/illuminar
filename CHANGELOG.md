# Changelog

## 2.0.0 - 2026-11-12

### Breaking
- PHP 8.1+, Laravel 10.15 – 13.x (dropped Laravel 8/9 and PHP 7.4/8.0).
- `enabled` defaults to `APP_ENV === 'local'`; UI/API protected by `Authorize` middleware (`viewIlluminar` gate or `allowed_environments`).
- New storage format (`illuminar.jsonl`). `dump()` returns `Support\PendingDump`.
- Default `queries.slow_time_ms` is 500.

### Security
- `/illuminar/data` and `/illuminar/clear` were available without any check, even with Illuminar disabled.
- Stored data is never `unserialize()`d any more (object injection); values are rendered at capture time.
- Mail previews rendered in a sandboxed iframe (raw mail HTML was injected into the page - XSS, style leaks).
- Sensitive HTTP headers, model `$hidden` attributes and `.env` secrets are masked.

### Fixed
- Any error inside a watcher (e.g. `vsprintf` on `?` inside a string literal) broke the application request.
- A value with invalid UTF-8 made `json_encode` fail and wiped the whole storage file.
- Storage was rewritten completely on every entry (O(n) per query) and lost entries under concurrent processes.
- Bindings: SQL containing `:` (time literal, `::cast`) was not substituted, `null` shown as `'null'`, quotes not escaped, `:name` broke `:name_full`.
- `illuminar()->dump()`, `->illuminar()` macros and track methods wrote data while Illuminar was disabled.
- Callers pointed to the package itself when installed from a path repository (symlink).
- Entry time stored as `H:i:s` only, so "x minutes ago" was wrong for entries from previous days.
- Slow queries were stored twice when both query watchers were enabled; inconsistent slow thresholds (`>` vs `<`, 5000 vs 10000).
- `trackModelEvents()` from the README threw an exception.
- `showEnv()` crashed on lines without `=` and did not parse quoted values.
- Empty arrays and other falsy cache values were dropped from dumps.
- Model events matched by substring (`Str::contains`), `created` stored appends/relations.
- UI: auto refresh stopped after typing a search; hardcoded `/illuminar` URLs; UI broke when assets were not published / stale after update.
- Frontend could not be built: dependencies were missing in `package.json`.

### Added
- Request profiles for HTTP requests, artisan commands and jobs: duration, memory, query count/time, slow queries, cache, HTTP calls, mails, logs, exceptions, dispatched jobs.
- Possible N+1 detection: repeated SQL with count, example query and the line of code that runs it.
- Log watcher (`Log::info()` & co.) with minimal level.
- SQLite storage (default): indexed by type and request, WAL for concurrent processes; falls back to the file driver without `pdo_sqlite`.
- MCP server for AI agents (`php artisan illuminar:mcp`) and Laravel Boost guidelines.
- Watchers can be enabled for every request via config/.env (`illuminar.track`).
- Request / command / job context for every entry, filter by context in the UI.
- Open-in-editor links (PhpStorm, VS Code, Cursor, Zed, ...) with Docker path mapping.
- `illuminar:tail` (with `--json`) and `illuminar:clear` commands.
- `illuminar($x)` shortcut, `Illuminar` facade, `dd()`, `watch()`, `clear()`, chainable track methods, collection & relation macros.
- Jobs: `processing` status, duration, attempts; HTTP: failed connections, duration, body truncation; exceptions: code snippet, previous exceptions, level; cache: store, TTL.
- Search inside dump contents and exception messages; entry counts per type.
- UI on Vue 3 + Vite: live/pause (pauses in background tabs), theme toggle, copy SQL, `/` to focus search. Bundle 1.47 MB → 374 KB.
