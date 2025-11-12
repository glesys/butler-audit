<?php

namespace Butler\Audit;

use Butler\Audit\Jobs\Audit as AuditJob;
use Butler\Audit\Testing\AuditData;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Dumpable;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @method \Butler\Audit\Audit entity(string|array|\Butler\Audot\Contracts\Auditable $type, mixed $identifier)
 * @method \Butler\Audit\Audit event(string $type, array $context = [])
 * @method \Butler\Audit\Audit eventContext(string $key, mixed $value)
 * @method \Butler\Audit\Audit initiator(string $initiator, array $context = [])
 * @method \Butler\Audit\Audit initiatorContext(string $key, mixed $value)
 *
 * @see \Butler\Audit\Audit
 */
class Auditor
{
    use Dumpable, Macroable {
        __call as macroCall;
    }

    protected $correlationId;
    protected $correlationTrail;

    protected bool $recording = false;

    protected array $recorded = [];

    private static $initiatorResolver;

    public function fake()
    {
        $this->recording = true;

        return $this;
    }

    public function log(Audit $audit): void
    {
        $data = $audit->toArray();

        if ($this->recording) {
            $this->recorded[] = new AuditData($data);
        } else {
            AuditJob::dispatch($data);
        }
    }

    public function assertLogged(string $eventName, ?Closure $callback = null): void
    {
        PHPUnit::assertTrue(
            $this->recorded($eventName, $callback)->isNotEmpty(),
            "The expected audit event [{$eventName}] was not logged."
        );
    }

    public function assertNotLogged(string $eventName, ?Closure $callback = null): void
    {
        PHPUnit::assertCount(
            0,
            $this->recorded($eventName, $callback),
            "A unexpected audit event [{$eventName}] was logged."
        );
    }

    public function assertNothingLogged(): void
    {
        PHPUnit::assertEmpty($this->recorded, 'Audit events were logged unexpectedly.');
    }

    public function assertLoggedCount(int $count): void
    {
        PHPUnit::assertCount($count, $this->recorded);
    }

    public function recorded(string $eventName, ?Closure $callback = null): Collection
    {
        $callback = $callback ?: fn () => true;

        return collect($this->recorded)->filter(fn (AuditData $data)
            => $data->event === $eventName
            && $callback($data));
    }

    public function correlationId(?string $correlationId = null): string
    {
        if (func_num_args() === 1) {
            $this->correlationId = $correlationId;
        }

        return $this->correlationId ??= request()->header('X-Correlation-ID', (string) str()->uuid());
    }

    public function correlationTrail(?string $correlationTrail = null): ?string
    {
        if (func_num_args() === 1) {
            $this->correlationTrail = $correlationTrail;
        }

        if ($this->correlationTrail) {
            return $this->correlationTrail;
        }

        if (! request()->hasHeader('X-Correlation-ID')) {
            return $this->correlationTrail = null;
        }

        $trail = str()->random(8);

        if ($existingTrail = request()->header('X-Correlation-Trail')) {
            return $this->correlationTrail = "{$existingTrail}:{$trail}";
        }

        return $this->correlationTrail = $trail;
    }

    public function httpHeaders(): array
    {
        return array_filter([
            'X-Correlation-ID' => $this->correlationId(),
            'X-Correlation-Trail' => $this->correlationTrail(),
        ]);
    }

    public function initiatorResolver(?Closure $resolver = null): ?Closure
    {
        if (func_num_args() === 1) {
            static::$initiatorResolver = $resolver;
        }

        return static::$initiatorResolver;
    }

    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return tap(new Audit($this))->{$method}(...$parameters);
    }
}
