# Illuminar

Illuminar is a Laravel package that provides a set of watchers to track various activities in your application. It is
designed to help developers monitor and debug their applications more effectively.

<img width="1237" alt="299806977-260b3bd6-db1d-469c-968f-2c68dd2a82af" src="https://github.com/adobrovolsky97/illuminar/assets/136475533/119599fe-8fec-4657-995e-0b54684f56a4">

## Features

- [**Dumps**](#dumps)

- [**DB Queries**](#db-queries--slow-queries-tracking): all queries including slow, duplicated, group duplicated queries

- [**Model Events Tracking**](#model-events-tracking)

- [**Jobs Tracking**](#jobs-tracking)

- [**Events Tracking**](#events-tracking)

- [**Cache Tracking**](#cache-tracking)

- [**Sent Mail Tracking & Mailable Previews**](#sent-mail-tracking--mailable-previews)

- [**HTTP Request Tracking**](#http-request-tracking)

- [**Environment Variables**](#environment-variables)

- [**Exceptions Tracking**](#exceptions-tracking)

## Installation

You can install the package via composer:

```bash
composer require adobrovolsky97/illuminar
```

If you don't use auto-discovery, add the ServiceProvider to the providers array in config/app.php

```bash
Adobrovolsky97\Illuminar\ServiceProvider::class,
```

## Configuration

You can configure Illuminar by publishing its configuration file:

```bash
php artisan vendor:publish --provider="Adobrovolsky97\Illuminar\ServiceProvider"
```

This will publish required `assets` to `public/vendor/illuminar` and `illuminar.php` config file to your `config` directory. In this file, you can
enable or disable Illuminar and configure its watchers.

## Usage

### Enabling Illuminar

Illuminar is enabled when `ILLUMINAR_ENABLED=true` in your `.env` file.

Debug data can be viewed at the `APP_URL/illuminar` route.

### Dumps

Illuminar provides a `illuminar()->dump(...$args)` function that can be used to dump variables to the illuminar screen.
It is similar to the `dd` helper function, but it does not terminate the script execution.

It is possible to add custom tags and colors to the dump, so it is easier to find it in the dump list.

```php
illuminar()->dump($user, $anotherUser)->tag('users')->red(); // This will add a dump with a red border and a "users" tag
illuminar()->dump('Hello World');
illuminar()->dump(['foo' => 'bar'])->tag('array')->orange();
illuminar()
->dump(function () {
    return 'Hello World';
})
->tag('closure')
->green();
illuminar()->dump('Some data')->die(); // This one will terminate the script execution
```

### DB Queries & Slow Queries Tracking

Illuminar can track all DB queries and slow queries. It will display the query, bindings, execution time, and query
caller.

```php
illuminar()->trackQueries(); // Enables DB queries tracking
User::all(); // This query will be tracked
illuminar()->stopTrackingQueries(); // Disables DB queries tracking

User::all(); // This query will not be tracked

illuminar()->trackSlowQueries(); // Enables slow queries tracking
User::all(); // This query will be tracked only if its execution time is more than x ms (execution time taken from the config)
illuminar()->stopTrackingSlowQueries(); // Disables slow queries tracking

User::query()
    ->where('name', 'John Doe')
    ->illuminar() // Allows to dump query while building it
    ->where('is_active', true)
    ->illuminar() // Will contain additional where clause
    ->get();
```

### Model Events Tracking

Illuminar can track all model events. It will display the event name, model event caller and attributes changes.

By default, it is tracking: 'restored', 'updated', 'created', 'deleted'. It is possible to update it in the config file.

```php
illuminar()->trackModelEvents(); // Enables model events tracking

$user = User::first();
$user->update(['name' => 'John Doe']); // This event will be tracked
$user->delete();

illuminar()->stopTrackingModelEvents(); // Disables model events tracking

User::create(['name' => 'John Doe']); // This event will not be tracked
```

### Jobs Tracking

Illuminar can track all jobs. It will display the job name, job caller, job status, and job payload.

```php
illuminar()->trackJobs(); // Enables jobs tracking
Job::dispatch();
illuminar()->stopTrackingJobs(); // Disables jobs tracking

Job::dispatch(); // This job will not be tracked
```

### Events Tracking

Illuminar can track all events. It will display the event name, event caller, and event payload.

```php
illuminar()->trackEvents(); // Enables events tracking
event(new UserRegistered($user)); // This event will be tracked
illuminar()->stopTrackingEvents(); // Disables events tracking

event(new UserRegistered($user)); // This event will not be tracked
```

### Cache Tracking

Illuminar can track all cache operations. It will display the cache operation name, cache operation caller, and cache
operation payload.

```php
illuminar()->trackCaches(); // Enables cache tracking
Cache::put('key', 'value', 60); // This cache operation will be tracked
illuminar()->stopTrackingCaches(); // Disables cache tracking

Cache::put('key', 'value', 60); // This cache operation will not be tracked
```

### Sent Mail Tracking & Mailable Previews

Illuminar can track all sent mails. It will display the mail subject, mail recipient, mail sender, and mail payload,
also it is possible to view mail content after it is being sent or event without sending it.

```php

illuminar()->trackMails(); // Enables mail tracking
Mail::to('recipient@illuminar.com')->send(new WelcomeMail()); // This mail will be tracked
illuminar()->stopTrackingMails(); // Disables mail tracking

Mail::to('recipient@illuminar.com')->send(new WelcomeMail()); // This mail will not be tracked

illuminar()->mailable(new WelcomeMail()); // Displays mailable preview
```

### HTTP Request Tracking

Illuminar can track all HTTP requests. It will display the request method, request url, request headers, request
payload, and request caller.

```php
illuminar()->trackHttpRequests(); // Enables request tracking
Http::get('https://illuminar.com'); // This request will be tracked
illuminar()->stopTrackingHttpRequests(); // Disables request tracking

Http::get('https://illuminar.com'); // This request will not be tracked
```

### Environment Variables

Illuminar can display all environment variables.

```php
illuminar()->showEnv(); // Enables environment variables display
```

### Exceptions Tracking

Illuminar can track all exceptions. It will display the exception data.

```php
illuminar()->trackExceptions(); // Enables exceptions tracking
throw new Exception('Something went wrong'); // This exception will be tracked
illuminar()->stopTrackingExceptions(); // Disables exceptions tracking

throw new Exception('Something went wrong'); // This exception will not be tracked
```

Illuminar stores data to the `storage/illuminar` directory and the data is being re-written on each request.

It could be used to debug the application not only on local but also on dev or production as it does not store a lot of data so the database will not be overloaded and could be easily disabled with .env variable.

## License

The Illuminar package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
