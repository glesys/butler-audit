<?php

namespace Butler\Audit\Bus;

use Butler\Audit\Jobs\Middleware\SetCorrelationId;

trait WithCorrelationId
{
    public $correlationId;
    public $correlationDepth;

    public function middleware()
    {
        return [new SetCorrelationId()];
    }
}
