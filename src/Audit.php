<?php

namespace Butler\Audit;

use ArrayAccess;
use Butler\Audit\Contracts\Auditable;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Audit implements ArrayAccess
{
    private array $entities = [];

    private string $event;
    private array $eventContext = [];

    private string $initiator;
    private array $initiatorContext = [];

    public function __construct(private Auditor $auditor)
    {
    }

    /**
     * @example entity('user', 1)
     * @example entity('user', [1, 2])
     * @example entity($auditable)
     * @example entity(['user' => 1, 'car' => 1])
     * @example entity(['user' => [1, 2])
     * @example entity([$auditable1, $auditable2])
     */
    public function entity(
        string|array|Auditable $type,
        mixed $identifier = null,
    ): self {
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
        if (! is_null($value) && ! is_scalar($value)) {
            throw new Exception('Event context value must be null or scalar.');
        }

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
        if (! is_null($value) && ! is_scalar($value)) {
            throw new Exception('Initiator context value must be null or scalar.');
        }

        $this->initiatorContext[] = compact('key', 'value');

        return $this;
    }

    public function log(string $event = null, array $eventContext = []): void
    {
        if ($event) {
            $this->event($event, $eventContext);
        }

        $this->auditor->log($this);
    }

    public function offsetSet($offset, $value): void
    {
        throw new Exception('Audit data may not be mutated using array access.');
    }

    public function offsetExists($offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetUnset($offset): void
    {
        throw new Exception('Audit data may not be mutated using array access.');
    }

    public function offsetGet($offset)
    {
        return $this->$offset ?? null;
    }

    public function toArray(): array
    {
        if (empty($this->initiator) and $resolver = $this->auditor->initiatorResolver()) {
            $this->initiator(...call_user_func($resolver));
        }

        throw_unless($this->event ?? false, 'Event is required.');
        throw_unless($this->entities, 'At least one entity is required.');
        throw_unless($this->initiator ?? false, 'Initiator is required.');

        return [
            'correlationId' => $this->auditor->correlationId(),
            'correlationTrail' => $this->auditor->correlationTrail(),
            'entities' => $this->entities,
            'event' => $this->event,
            'eventContext' => $this->eventContext,
            'initiator' => $this->initiator,
            'initiatorContext' => $this->initiatorContext,
            'occurredAt' => now()->toRfc3339String(),
        ];
    }

    public function __call($name, $arguments)
    {
        $event = str($name)->camel();

        // NOTE: Prefix "event" with entity type when there is only one unique entity type.
        $uniqueEntityTypes = collect($this->entities)->pluck('type')->unique();
        if ($uniqueEntityTypes->count() === 1) {
            $type = str($uniqueEntityTypes->first())->camel();
            $event = $event->start($type . '.');
        }

        $context = Collection::wrap($arguments[0] ?? [])->toArray();

        return $this->log($event, $context);
    }
}
