<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Facades\Auditor;
use GrahamCampbell\TestBenchCore\ServiceProviderTrait;

class ServiceProviderTest extends AbstractTestCase
{
    use ServiceProviderTrait;

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
}
