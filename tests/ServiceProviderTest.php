<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Bus\Dispatcher;
use Butler\Audit\Facades\Auditor;
use Butler\Audit\Tests\JobWithoutCorrelationId;
use GrahamCampbell\TestBenchCore\ServiceProviderTrait;
use Illuminate\Bus\Dispatcher as BaseDispatcher;
use Illuminate\Queue\Events\JobProcessed;

class ServiceProviderTest extends AbstractTestCase
{
    use ServiceProviderTrait;

    protected function setUp(): void
    {
        putenv('APP_RUNNING_IN_CONSOLE=true');

        parent::setUp();
    }

    public function test_auditor_is_injectable()
    {
        $this->assertIsInjectable(Auditor::class);
    }

    public function test_url_config()
    {
        $this->assertEquals(
            'https://localhost/log',
            $this->app->config->get('butler.audit.url')
        );
    }

    public function test_token_config()
    {
        $this->assertEquals(
            'secret',
            $this->app->config->get('butler.audit.token')
        );
    }

    public function test_Dispatcher_is_extended()
    {
        $this->assertInstanceOf(Dispatcher::class, app(BaseDispatcher::class));
    }

    public function test_correlation_id_is_reset_after_each_queued_job()
    {
        $this->assertTrue(app('events')->hasListeners(JobProcessed::class));

        $correlationId = Auditor::correlationId();

        event(new JobProcessed('connection', new JobWithoutCorrelationId()));

        $this->assertNotEquals($correlationId, Auditor::correlationId());
    }
}
