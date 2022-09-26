<?php

namespace Butler\Audit\Contracts;

interface Auditable
{
    public function auditorType(): string;

    public function auditorIdentifier();
}
