<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Facades\Auditor;
use Illuminate\Support\Facades\Queue;

class DispatcherTest extends AbstractTestCase
{
    public function test_it_sets_correlation_for_job_using_WithCorrelationId_trait()
    {
        Auditor::fake();
        Queue::fake();

        Auditor::correlationId('correlation-id');
        Auditor::correlationTrail('correlation-tail');

        dispatch(new JobWithCorrelation());

        Queue::assertPushed(fn (JobWithCorrelation $job)
            => $job->correlationId === 'correlation-id'
            && $job->correlationTrail === 'correlation-tail');
    }

    public function test_it_does_not_set_correlation_for_job_not_using_WithCorrelationId_trait()
    {
        Auditor::fake();
        Queue::fake();

        dispatch(new JobWithoutCorrelation());

        Queue::assertPushed(JobWithoutCorrelation::class);

        Queue::assertNotPushed(fn (JobWithoutCorrelation $job)
            => property_exists($job, 'correlationId')
            && property_exists($job, 'correlationTrail'));
    }
}
