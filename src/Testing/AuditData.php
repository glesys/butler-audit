<?php

namespace Butler\Audit\Testing;

use Illuminate\Support\Fluent;

class AuditData extends Fluent
{
    public function hasEventContext(string $key, $value): bool
    {
        return $this->contextHas($this->eventContext, $key, $value);
    }

    public function hasInitiatorContext(string $key, $value): bool
    {
        return $this->contextHas($this->initiatorContext, $key, $value);
    }

    public function hasEntity(string $type, $identifier): bool
    {
        return collect($this->entities)->contains(fn ($entity)
            => $entity['type'] === $type
            && $entity['identifier'] === $identifier);
    }

    private function contextHas(array $context, string $key, $value): bool
    {
        return collect($context)->contains(fn ($context)
            => $context['key'] === $key
            && $context['value'] === $value);
    }
}
