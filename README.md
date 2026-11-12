# Illuminar

Illuminar is a debug tool for Laravel: dumps, DB queries (slow / duplicated / N+1), model events, jobs, events,
cache, mails, outgoing HTTP requests and exceptions - in a live web UI or right in your terminal.

<img width="1237" alt="Illuminar" src="https://github.com/adobrovolsky97/illuminar/assets/136475533/119599fe-8fec-4657-995e-0b54684f56a4">

- [Installation](#installation)
- [Configuration](#configuration)
- [Dumps](#dumps)
- [Tracking](#tracking): [queries](#db-queries), [models](#model-events), [jobs](#jobs), [events](#events),
  [cache](#cache), [mails](#mails--mailable-previews), [http requests](#http-requests), [exceptions](#exceptions)
- [Terminal](#terminal)
- [Security](#security)
- [Upgrading from 1.x](#upgrading-from-1x)

## Requirements

PHP 8.1+, Laravel 10.15 - 13.x

## Installation

```bash
composer require adobrovolsky97/illuminar --dev
```

Open `APP_URL/illuminar`. No assets publishing is needed - the UI is served by the package.

## Configuration

```bash
php artisan vendor:publish --tag=illuminar-config
```

The most useful `.env` options:

```dotenv
ILLUMINAR_ENABLED=true                # default: true only when APP_ENV=local
ILLUMINAR_TRACK_QUERIES=true          # track watchers for every request without changing code
ILLUMINAR_TRACK_SLOW_QUERIES=false
ILLUMINAR_TRACK_MODELS=false
ILLUMINAR_TRACK_JOBS=false
ILLUMINAR_TRACK_EVENTS=false
ILLUMINAR_TRACK_CACHE=false
ILLUMINAR_TRACK_MAILS=false
ILLUMINAR_TRACK_HTTP_REQUESTS=false
ILLUMINAR_TRACK_EXCEPTIONS=true
ILLUMINAR_TRACK_LOGS=false
ILLUMINAR_TRACK_REQUESTS=true         # profile of every request / command / job (queries, N+1, memory...)

ILLUMINAR_STORAGE=sqlite              # sqlite (indexed, default) or file

ILLUMINAR_EDITOR=phpstorm             # phpstorm, vscode, cursor, windsurf, sublime, zed, idea or a template "myeditor://open?file=%file&line=%line"
ILLUMINAR_REMOTE_PATH=/var/www/html   # Docker / Sail: path inside the container...
ILLUMINAR_LOCAL_PATH=/Users/me/project # ...and on your machine, so "open in editor" links work

ILLUMINAR_THEME=dark
ILLUMINAR_PATH=illuminar
```

When Illuminar is disabled every `illuminar()` call is a no-op, so a forgotten dump never hurts production.

## Dumps

```php
illuminar($user, $order);                         // shortcut for illuminar()->dump(...)
illuminar($user)->tag('users')->red();            // tag + color: red, orange, blue, green, purple, gray
illuminar()->dump('Hello World');
illuminar()->dd($user);                           // dump and die
Illuminar::dump($user);                           // facade

collect($items)->illuminar()->map(...);           // dump collection in the middle of a chain

User::query()
    ->where('name', 'John')
    ->illuminar()                                 // dump SQL while building it
    ->where('is_active', true)
    ->illuminar()
    ->get();

illuminar()->showEnv();                           // .env variables, secrets are masked
```

Blade: `@illuminar($user)`

Every entry has a **caller** (click it to open the file in your editor) and a **context** - the request, artisan
command or job it belongs to. Click the context to see everything that happened during that request.

## Tracking

Enable a watcher for every request in the config / `.env` (see above), or only for a part of code:

```php
illuminar()->trackQueries()->trackModels();       // methods are chainable
// ...
illuminar()->stopTrackingQueries();

illuminar()->trackAll();
illuminar()->stopTrackingAll();

// Track only while the callback runs
$result = illuminar()->watch(fn () => $service->handle(), ['query', 'model']);
```

### DB queries

```php
illuminar()->trackQueries();       // all queries (with bindings substituted, ready to copy & run)
illuminar()->trackSlowQueries();   // only queries slower than illuminar.queries.slow_time_ms
```

In the UI you can group duplicated queries, show only duplicates (N+1) or only slow queries.
`Adobrovolsky97\Illuminar\Events\SlowQueryFound` is dispatched for every slow query.

### Model events

```php
illuminar()->trackModels();        // alias: trackModelEvents()
```

Tracks `created`, `updated`, `deleted`, `restored` (configurable) with changed attributes. `$hidden` attributes are masked.

### Jobs

```php
illuminar()->trackJobs();
```

Shows job properties, status (`pending` → `processing` → `processed` / `failed`), duration, attempts and exception.
Status is updated by the queue worker even if the worker runs in another process.

### Events

```php
illuminar()->trackEvents();
```

### Cache

```php
illuminar()->trackCaches();        // alias: trackCache()
```

### Mails & mailable previews

```php
illuminar()->trackMails();
illuminar()->mailable(new WelcomeMail($user));                          // preview without sending
illuminar()->mailable((new InvoicePaid($invoice))->toMail($user));      // notification preview
```

Mail previews are rendered in a sandboxed iframe.

### HTTP requests

```php
illuminar()->trackHttpRequests();
```

Requests made with the `Http` client including failed connections. Sensitive headers (`Authorization`, cookies,
API keys) are masked, large bodies are truncated (`illuminar.http`).

### Exceptions

Reported exceptions are tracked by default (`ILLUMINAR_TRACK_EXCEPTIONS`), with a code snippet, trace and previous exceptions.

### Logs

`Log::info()` & co. with level and context (`ILLUMINAR_TRACK_LOGS`, minimal level in `illuminar.logs.min_level`).
Exceptions logged via `report()` are shown by the exception watcher instead.

### Request profiles & N+1

Enabled by default (`ILLUMINAR_TRACK_REQUESTS`). After every HTTP request, artisan command and queued job Illuminar stores
a summary: status / exit code, duration, peak memory, number and total time of queries, slow queries, cache hits / misses,
HTTP calls, mails, logs, exceptions and dispatched jobs.

The same SQL executed at least `illuminar.requests.n_plus_one_threshold` (3) times is reported as a possible N+1 with an
example query and **the line of code that runs it**. Profiles work without `ILLUMINAR_TRACK_QUERIES`, so you can keep
query tracking off and still see problems. Illuminar's own routes and noisy commands (`queue:work`, `horizon`, ...) are
ignored, see `illuminar.requests`.

## Storage

Entries are kept in `storage/illuminar/illuminar.sqlite` (WAL mode, indexes by type and request), only the requested
part is loaded on each UI poll. Without the `pdo_sqlite` extension, or with `ILLUMINAR_STORAGE=file`, a JSON lines file is
used. A custom driver is any class implementing `StorageDriverInterface` (optionally `FiltersEntries`).

## Terminal

```bash
php artisan illuminar:tail                         # stream new entries
php artisan illuminar:tail --type=query --type=exception --search=users
php artisan illuminar:tail --once --json           # latest entries as JSON lines (scripts, AI agents)
php artisan illuminar:clear
```

## AI agents (MCP)

`php artisan illuminar:mcp` starts an [MCP](https://modelcontextprotocol.io) server (stdio, no extra dependencies),
so Claude Code, Cursor and other agents can read what happened in your app instead of asking you to copy-paste it:

```bash
claude mcp add illuminar -- php artisan illuminar:mcp
```

```json
// .cursor/mcp.json, .mcp.json ...
{"mcpServers": {"illuminar": {"command": "php", "args": ["artisan", "illuminar:mcp"]}}}
```

With Docker / Sail run it inside the container: `"command": "./vendor/bin/sail", "args": ["artisan", "illuminar:mcp"]`.

Tools: `get_last_exception` (with all queries / dumps / logs of that request), `list_requests` (profiles, `only_problems`
for N+1 / 5xx / failed jobs), `list_entries` (filters by type, search, request), `get_entry` (full details as plain text)
and `clear_entries`.

The package ships [Laravel Boost](https://github.com/laravel/boost) guidelines (`resources/boost/guidelines`), so agents
in projects using Boost know to debug with `illuminar()` and these tools.

## Security

The UI and its API are protected by the `Authorize` middleware:

- 404 when Illuminar is disabled;
- if a `viewIlluminar` gate is defined it is used, otherwise only environments from `illuminar.allowed_environments` (`local`) are allowed.

```php
// AppServiceProvider::boot()
Gate::define('viewIlluminar', fn ($user = null) => in_array(optional($user)->email, ['admin@example.com']));
```

Data is stored in `storage/illuminar/illuminar.jsonl` (append-only, safe for concurrent processes,
compacted automatically, limited by `illuminar.storage.limit`).

## Upgrading from 1.x

- PHP 8.1+ and Laravel 10.15+ are required.
- `ILLUMINAR_ENABLED` defaults to `APP_ENV === 'local'` (was `true`).
- The UI is protected: define the `viewIlluminar` gate to use it outside of `local`.
- Published assets in `public/vendor/illuminar` are not used anymore and can be deleted.
- `illuminar()->dump()` returns `Support\PendingDump` (same `tag()`/colors/`die()` API).
- Default `queries.slow_time_ms` is 500 ms (was 5000).
- Storage format changed (`illuminar.jsonl`), old entries are not displayed.

## Development

```bash
composer install && composer test
npm install && npm run build      # rebuild UI to public/
npm run dev                       # rebuild on change
```

## License

The Illuminar package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
