<?php

namespace Butler\Audit;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Str;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        $this->app->singleton('butler-audit-correlation-id', function () {
            return request()->header('X-Correlation-ID', (string) Str::uuid());
        });

        $this->app->bind(Auditor::class, fn () => new Auditor(app('butler-audit-correlation-id')));

        PendingRequest::macro('withCorrelationId', fn () => $this->withHeaders([
            'X-Correlation-ID' => app('butler-audit-correlation-id'),
        ]));
    }

    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/butler.php' => config_path('butler.php')], 'config');
    }
}
