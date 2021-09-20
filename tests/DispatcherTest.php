<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Facades\Auditor;
use Illuminate\Support\Facades\Queue;

class DispatcherTest extends AbstractTestCase
{
    public function test_it_sets_correlation_id_for_job_using_WithCorrelationId_trait()
    {
        Auditor::fake();
        Queue::fake();

        Auditor::correlationId('a-correlation-id');

        dispatch(new JobWithCorrelationId());

        Queue::assertPushed(
            fn (JobWithCorrelationId $job) => $job->correlationId === 'a-correlation-id'
        );
    }

    public function test_it_does_not_set_correlation_id_for_job_not_using_WithCorrelationId_trait()
    {
        Auditor::fake();
        Queue::fake();

        dispatch(new JobWithoutCorrelationId());

        Queue::assertPushed(JobWithoutCorrelationId::class);

        Queue::assertNotPushed(
            fn (JobWithoutCorrelationId $job) => property_exists($job, 'correlationId')
        );
    }
}
