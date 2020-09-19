<?php

namespace Butler\Audit\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait IsAuditable
{
    public function auditorType(): string
    {
        return Str::camel(class_basename(static::class));
    }

    public function auditorIdentifier()
    {
        if ($this instanceof Model) {
            return $this->getKey();
        }

        throw new Exception('Auditor entity identifier not found.');
    }
}
