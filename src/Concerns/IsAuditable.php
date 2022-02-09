<?php

namespace Butler\Audit\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;

trait IsAuditable
{
    public function auditorType(): string
    {
        return str(class_basename(static::class))->camel();
    }

    public function auditorIdentifier()
    {
        if ($this instanceof Model) {
            return $this->getKey();
        }

        throw new Exception('Auditor entity identifier not found.');
    }
}
