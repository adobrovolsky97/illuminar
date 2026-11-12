## Illuminar (debugging)

This application uses Illuminar (`adobrovolsky97/illuminar`) to record debug data: dumps, SQL queries,
request profiles with N+1 detection, exceptions, logs, jobs, events, cache, mails and outgoing HTTP requests.

- Prefer `illuminar($value)` over `dd()` / `dump()` while debugging: it does not stop the request and the value can be read later.
  `illuminar($value)->tag('checkout')` makes it easy to find. Remove the calls when done.
- Read recorded data with the `illuminar` MCP server tools (`get_last_exception`, `list_requests`, `list_entries`, `get_entry`)
  or with `php artisan illuminar:tail --once --json --type=exception --type=request`.
- To investigate a bug: call `clear_entries` (or `php artisan illuminar:clear`), reproduce the request, then inspect
  `get_last_exception` / `list_requests` and the entries of that request (`context_id`).
- Possible N+1 problems are listed in request profiles (`n_plus_one`: SQL, count and the line of code that runs it).
- Watchers can be enabled without code changes in `.env`: `ILLUMINAR_TRACK_QUERIES=true`, `ILLUMINAR_TRACK_LOGS=true`, etc.
