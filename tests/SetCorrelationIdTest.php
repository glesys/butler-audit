<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Facades\Auditor;
use Butler\Audit\Jobs\Middleware\SetCorrelationId;

class SetCorrelationIdTest extends AbstractTestCase
{
    public function test_correlation_id_is_set_when_job_is_using_WithCorrelationId_trait()
    {
        Auditor::fake();

        $job = new JobWithCorrelationId();
        $job->correlationId = 'correlation-id';

        (new SetCorrelationId())->handle($job, fn ($job) => true);

        $this->assertEquals('correlation-id', Auditor::correlationId());
    }

    public function test_correlation_id_is_not_set_when_job_is_not_using_WithCorrelationId_trait()
    {
        Auditor::fake();

        $correlationId = Auditor::correlationId();

        $job = new JobWithoutCorrelationId();

        (new SetCorrelationId())->handle($job, fn ($job) => true);

        $this->assertEquals($correlationId, Auditor::correlationId());
    }
}
