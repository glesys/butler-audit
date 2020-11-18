<?php

namespace Butler\Audit;

use ArrayAccess;
use Butler\Audit\Contracts\Auditable;
use Butler\Audit\Jobs\Audit as AuditJob;
use Closure;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Auditor implements ArrayAccess
{
    private string $correlationId;

    private array $entities = [];

    private string $event;
    private array $eventContext = [];

    private string $initiator;
    private array $initiatorContext = [];

    private static $initiatorResolver;

    public function __construct(string $correlationId)
    {
        $this->correlationId = $correlationId;

        if (isset(static::$initiatorResolver)) {
            $this->initiator(...call_user_func(static::$initiatorResolver));
        }
    }

    /**
     * @example entity('user', 1)
     * @example entity('user', [1, 2])
     * @example entity($auditable)
     * @example entity(['user' => 1, 'car' => 1])
     * @example entity(['user' => [1, 2])
     * @example entity([$auditable1, $auditable2])
     *
     * @param  string|array|\Butler\Audot\Contracts\Auditable  $type
     * @param  mixed  $identifier
     */
    public function entity($type, $identifier = null): self
    {
        if (is_string($type) && $identifier) {
            foreach (Arr::wrap($identifier) as $identifier) {
                $this->entities[] = compact('type', 'identifier');
            }
        } elseif (is_array($type)) {
            foreach ($type as $key => $value) {
                if ($value instanceof Auditable) {
                    $this->entity($value);
                } else {
                    $this->entity($key, $value);
                }
            }
        } elseif ($type instanceof Auditable) {
            $this->entity($type->auditorType(), $type->auditorIdentifier());
        } else {
            throw new Exception('Invalid entity.');
        }

        return $this;
    }

    public function event(string $event, array $context = []): self
    {
        $this->event = $event;

        foreach ($context as $key => $value) {
            $this->eventContext($key, $value);
        }

        return $this;
    }

    public function eventContext(string $key, $value = null): self
    {
        $this->eventContext[] = compact('key', 'value');

        return $this;
    }

    public function initiator(string $initiator, array $context = []): self
    {
        $this->initiator = $initiator;

        foreach ($context as $key => $value) {
            $this->initiatorContext($key, $value);
        }

        return $this;
    }

    public function initiatorContext(string $key, $value = null): self
    {
        $this->initiatorContext[] = compact('key', 'value');

        return $this;
    }

    public static function setInitiatorResolver(Closure $resolver): void
    {
        static::$initiatorResolver = $resolver;
    }

    public static function unsetInitiatorResolver(): void
    {
        static::$initiatorResolver = null;
    }

    public function log(string $event = null, array $eventContext = []): void
    {
        if ($event) {
            $this->event($event, $eventContext);
        }

        throw_unless($this->event ?? false, Exception::class, 'Event is required.');
        throw_unless($this->entities, Exception::class, 'At least one entity is required.');
        throw_unless($this->initiator ?? false, Exception::class, 'Initiator is required.');

        $this->dispatch([
            'correlationId' => $this->correlationId,
            'entities' => $this->entities,
            'event' => $this->event,
            'eventContext' => $this->eventContext,
            'initiator' => $this->initiator,
            'initiatorContext' => $this->initiatorContext,
            'occurredAt' => now()->toRfc3339String(),
        ]);
    }

    protected function dispatch(array $data): void
    {
        AuditJob::dispatch($data);
    }

    public function offsetSet($offset, $value): void
    {
        throw new Exception('Auditor data may not be mutated using array access.');
    }

    public function offsetExists($offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetUnset($offset): void
    {
        throw new Exception('Auditor data may not be mutated using array access.');
    }

    public function offsetGet($offset)
    {
        return $this->$offset ?? null;
    }

    public function __call($name, $arguments)
    {
        $event = Str::of($name)->camel();

        // NOTE: Prefix "event" with entity type when there is only one unique entity type.
        $uniqueEntityTypes = collect($this->entities)->pluck('type')->unique();
        if ($uniqueEntityTypes->count() === 1) {
            $type = Str::camel($uniqueEntityTypes->first());
            $event = $event->start($type . '.');
        }

        $context = Collection::wrap($arguments[0] ?? [])->toArray();

        return $this->log($event, $context);
    }
}
