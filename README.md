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
    "entities": [
        {
            "type": "user",
            "identifier": 1
        }
    ],
    "event": "subscribed",
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

Set a "initiator resolver" for your application to avoid setting initiator
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

### Http client macro

Use the "withCorrelationId" macro to add the "X-Correlation-ID" header when sending requests with the Http client.

In the example below, both "audits" will have the same correlation-id.

```php
// Service A
audit($user)->signedUp();
Http::withCorrelationId()->post('https://service-b.example/welcome-email', $user);

// Service B
audit($user)->welcomed();
```

## AuditorFake

Instead of [faking the queue](https://laravel.com/docs/master/mocking#queue-fake) in your tests and e.g. `Queue::assertPushed(function (AuditJob) {})` you can use the [AuditorFake](src/Testing/AuditorFake.php).

```php
public function test_welcome_user()
{
    Auditor::fake();

    // Assert that nothing was logged...
    Auditor::nothingLogged();

    // Perform user welcoming...

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
vendor/bin/phpcs
```

## How To Contribute

Development happens at GitHub; any typical workflow using Pull Requests are welcome. In the same spirit, we use the GitHub issue tracker for all reports (regardless of the nature of the report, feature request, bugs, etc.).

All changes are supposed to be covered by unit tests, if testing is impossible or very unpractical that warrants a discussion in the comments section of the pull request.

### Code standard

As the library is intended for use in Laravel applications we encourage code standard to follow [upstream Laravel practices](https://laravel.com/docs/master/contributions#coding-style) - in short that would mean [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) and [PSR-4](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md).
