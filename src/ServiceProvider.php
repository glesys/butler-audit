<?php

namespace Butler\Audit;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Butler\Audit\Facades\Auditor;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        PendingRequest::macro('withCorrelationId', fn () => $this->withHeaders([
            'X-Correlation-ID' => Auditor::correlationId(),
        ]));
    }

    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/butler.php' => config_path('butler.php')], 'config');
    }
}
