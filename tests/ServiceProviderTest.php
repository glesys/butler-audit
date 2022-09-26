<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Bus\Dispatcher;
use Butler\Audit\Facades\Auditor;
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

    public function test_default_initiator_resolver_resolves_console()
    {
        Auditor::fake();

        audit('foo', 123)->bar();

        Auditor::assertLogged('foo.bar', fn ($data)
            => $data->initiator === 'console'
            && $data->hasInitiatorContext('hostname', gethostname()));
    }

    public function test_audit_initiator_resolver_resolves_web_user()
    {
        putenv('APP_RUNNING_IN_CONSOLE=false');

        $this->refreshApplication();

        Auditor::fake();

        audit('foo', 123)->bar();

        Auditor::assertLogged('foo.bar', fn ($data)
            => $data->initiator === '127.0.0.1'
            && $data->hasInitiatorContext('userAgent', 'Symfony'));
    }

    public function test_Dispatcher_is_extended()
    {
        $this->assertInstanceOf(Dispatcher::class, app(BaseDispatcher::class));
    }

    public function test_correlation_id_and_trail_is_reset_after_each_queued_job()
    {
        $this->assertTrue(app('events')->hasListeners(JobProcessed::class));

        $correlationId = Auditor::correlationId();
        $correlationTrail = Auditor::correlationTrail('aaaa');

        event(new JobProcessed('connection', new JobWithoutCorrelation()));

        $this->assertNotEquals($correlationId, Auditor::correlationId());
        $this->assertNull(Auditor::correlationTrail());
    }
}
