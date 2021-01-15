<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Audit;
use Butler\Audit\ServiceProvider;
use GrahamCampbell\TestBench\AbstractPackageTestCase;

abstract class AbstractTestCase extends AbstractPackageTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Audit::unsetInitiatorResolver();
    }

    protected function getServiceProviderClass($app)
    {
        return ServiceProvider::class;
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('butler.audit', [
            'url' => 'https://localhost/log',
            'token' => 'secret',
            'driver' => 'http',
        ]);
    }
}
