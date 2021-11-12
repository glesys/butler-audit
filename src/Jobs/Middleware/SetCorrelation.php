<?php

namespace Butler\Audit\Jobs\Middleware;

use Butler\Audit\Bus\WithCorrelation;
use Butler\Audit\Facades\Auditor;

class SetCorrelation
{
    public function handle($job, $next)
    {
        if (in_array(WithCorrelation::class, class_uses_recursive($job))) {
            Auditor::correlationId($job->correlationId);
            Auditor::correlationTrail($job->correlationTrail);
        }

        return $next($job);
    }
}
