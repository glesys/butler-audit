<?php

namespace Butler\Audit\Jobs\Middleware;

use Butler\Audit\Bus\WithCorrelationId;
use Butler\Audit\Facades\Auditor;

class SetCorrelationId
{
    public function handle($job, $next)
    {
        if (in_array(WithCorrelationId::class, class_uses_recursive($job))) {
            Auditor::correlationId($job->correlationId);
        }

        return $next($job);
    }
}
