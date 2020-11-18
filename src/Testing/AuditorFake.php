<?php

namespace Butler\Audit\Testing;

use Butler\Audit\Auditor;
use Closure;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;

class AuditorFake extends Auditor
{
    private $logged = [];

    public function assertLogged(string $eventName, Closure $callback = null): void
    {
        PHPUnit::assertTrue(
            $this->logged($eventName, $callback)->isNotEmpty(),
            "The expected audit event [{$eventName}] was not logged."
        );
    }

    public function assertNotLogged(string $eventName, Closure $callback = null): void
    {
        PHPUnit::assertCount(
            0,
            $this->logged($eventName, $callback),
            "A unexpected audit event [{$eventName}] was logged."
        );
    }

    public function assertNothingLogged()
    {
        PHPUnit::assertEmpty($this->logged, 'Audit events were logged unexpectedly.');
    }

    public function logged(string $eventName, Closure $callback = null): Collection
    {
        $callback = $callback ?: fn () => true;

        return collect($this->logged)->filter(fn (AuditData $data)
            => $data->event === $eventName
            && $callback($data));
    }

    protected function dispatch(array $data): void
    {
        $this->logged[] = new AuditData($data);
    }
}
