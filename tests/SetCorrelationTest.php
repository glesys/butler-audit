<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Facades\Auditor;
use Butler\Audit\Jobs\Middleware\SetCorrelation;

class SetCorrelationTest extends AbstractTestCase
{
    public function test_correlation_is_set_when_job_is_using_WithCorrelationId_trait()
    {
        Auditor::fake();

        $job = new JobWithCorrelation();
        $job->correlationId = 'correlation-id';
        $job->correlationTrail = 'correlation-trail';

        (new SetCorrelation())->handle($job, fn ($job) => true);

        $this->assertEquals('correlation-id', Auditor::correlationId());
        $this->assertEquals('correlation-trail', Auditor::correlationTrail());
    }

    public function test_correlation_is_not_set_when_job_is_not_using_WithCorrelationId_trait()
    {
        Auditor::fake();

        $correlationId = Auditor::correlationId();

        $job = new JobWithoutCorrelation();

        (new SetCorrelation())->handle($job, fn ($job) => true);

        $this->assertEquals($correlationId, Auditor::correlationId());
    }
}
