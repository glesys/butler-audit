:construction: **Not ready for production.**

# Butler Audit

Laravel package for sending audit events to a remote endpoint.

## Example

```php
audit($user)->subscribed(['months' => 12]);
```

```http
POST /log HTTP/1.1
Host: example.local
Accept: application/json
Content-Type: application/json
Authorization: Bearer secret

{
    "correlationId": "d9afea6a-14ed-4777-ae2f-a4d8baf4d5b7",
    "correlationTrail": "Mv9Jd6VM:GaIngT2j",
    "entities": [
        {
            "type": "user",
            "identifier": 1
        }
    ],
    "event": "user.subscribed",
    "eventContext": [
        {
            "key": "months",
            "value": 12
        }
    ],
    "initiator": "service-a",
    "occurredAt": 1600432185
}
```

## Getting Started

```bash
composer require glesys/butler-audit
php artisan vendor:publish --provider="Butler\Audit\ServiceProvider" --tag=config
```

## Configure

```env
BUTLER_AUDIT_DRIVER=http
BUTLER_AUDIT_URL=https://example.local/log
BUTLER_AUDIT_TOKEN=secret
```

Make sure you have a [queue](https://laravel.com/docs/master/queues) configured to speed up your application.

### Log driver

When developing you can use the log driver to prevent http requests being sent.

```env
BUTLER_AUDIT_DRIVER=log
```

### Initiator resolver

A default "initiator resolver" is registered in the [ServiceProvider](src/ServiceProvider.php).

Your application can have its own resolver to avoid setting initiator
manually for every audit call.
You can still use `initiator()` and `initiatorContext()` to override the values set by the resolver.

```php
Auditor::initiatorResolver(fn () => [
    auth()->id(),
    [
        'ip' => request()->ip(),
        'userAgent' => request()->userAgent(),
    ]
]);
```

You can disable the default resolver by setting `butler.audit.default_initiator_resolver` to `false`.

## Auditable

You can pass "Auditables" to the helper method.

```php
class Car extends Fluent implements Auditable
{
    public function auditorType(): string
    {
        return $this->type;
    }

    public function auditorIdentifier()
    {
        return $this->id;
    }
}

$car = new Car(['id' => 1, 'type' => 'volvo']);

audit($car)->started(); // equivalent to audit(['volvo', 1])->started();
```

### Trait for Eloquent models

For convenience there is a `IsAuditable` trait that can be used by eloquent models.

```php
class User extends Model implements Auditable
{
    use IsAuditable;
}

$user = User::find(1);

audit($user)->subscribed(); // equivalent to audit(['user', 1])->subscribed();
```

## X-Correlation-ID

We use "X-Correlation-ID" header to "relate" audits.

A "X-Correlation-Trail" header is also used to figure out the order of events
without relying on the events `occured_at`, see the example json below.

### Http client macro

Use the "withCorrelation" macro to add the "X-Correlation-ID" header when sending requests with the Http client.

In the example below, all "audits" will have the same correlation-id.

```php
// Service A
audit($user)->signedUp();
Http::withCorrelation()->post('https://service-b.example/welcome-email', $user);

// Service B
audit($user)->welcomed();
Http::withCorrelation()->post('https://service-c.example/notify-staff');

// Service C
audit($employee)->notified();
```

The requests sent to your configured `BUTLER_AUDIT_URL` will look something like:

```json
{
    "initiator": "api",
    "event": "user.signedUp",
    "correlationId": "92a55a99-82c1-4129-a587-96006f6aac82",
    "correlationTrail": null
}

{
    "initiator": "service-a",
    "event": "user.welcomed",
    "correlationId": "92a55a99-82c1-4129-a587-96006f6aac82",
    "correlationTrail": "Mv9Jd6VM"
}

{
    "initiator": "service-b",
    "event": "employee.notified",
    "correlationId": "92a55a99-82c1-4129-a587-96006f6aac82",
    "correlationTrail": "Mv9Jd6VM:GaIngT2j"
}
```

### Queued jobs

The trait `WithCorrelation` can be used on queable jobs that needs the same correlation id as the request.

#### How it works

1. A job using the `WithCorrelation` trait is dispatched to the queue.
1. Our `Dispatcher` will set a `correlationId` property on the job.
1. The job is handled by a worker.
1. The middleware `SetCorrelation` will tell `Auditor` to use the correlation id from the job.

Extending the dispatcher can be disabled by setting `butler.audit.extend_bus_dispatcher` to `false`.

## Auditor Fake

Instead of [faking the queue](https://laravel.com/docs/master/mocking#queue-fake) in your tests and e.g. `Queue::assertPushed(function (AuditJob) {})` you can fake requests, see example below.

```php
public function test_welcome_user()
{
    Auditor::fake();

    // Assert that nothing was logged...
    Auditor::nothingLogged();

    // Perform user welcoming...

    // Assert 1 event was logged...
    Auditor::assertLoggedCount(1);

    // Assert a event was logged...
    Auditor::assertLogged('user.welcomed');

    // Assert a event with context, initiator and entity was logged...
    Auditor::assertLogged('user.welcomed', fn (AuditData $audit)
        => $audit->initiator === 'service-a'
        && $audit->hasEntity('user', 1)
        && $audit->hasEventContext('months', 12)
    );
}
```

## Testing

```shell
vendor/bin/phpunit
vendor/bin/pint --test
```

## How To Contribute

Development happens at GitHub; any typical workflow using Pull Requests are welcome. In the same spirit, we use the GitHub issue tracker for all reports (regardless of the nature of the report, feature request, bugs, etc.).

All changes are supposed to be covered by unit tests, if testing is impossible or very unpractical that warrants a discussion in the comments section of the pull request.

### Code standard

As the library is intended for use in Laravel applications we encourage code standard to follow [upstream Laravel practices](https://laravel.com/docs/master/contributions#coding-style) - in short that would mean [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) and [PSR-4](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md).
