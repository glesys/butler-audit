<?php

namespace Butler\Audit\Bus;

use Butler\Audit\Jobs\Middleware\SetCorrelation;

trait WithCorrelation
{
    public $correlationId;
    public $correlationTrail;

    public function middleware()
    {
        return [new SetCorrelation()];
    }
}
