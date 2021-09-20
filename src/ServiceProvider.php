<?php

namespace Butler\Audit;

use Butler\Audit\Bus\Dispatcher as BusDispatcher;
use Butler\Audit\Facades\Auditor;
use Illuminate\Bus\Dispatcher as BaseBusDispatcher;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        $this->addPendingRequestMacro();
        $this->extendBusDispatcher();
    }

    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/butler.php' => config_path('butler.php')], 'config');

        $this->listenForJobProcessedEvent();
    }

    private function addPendingRequestMacro(): void
    {
        PendingRequest::macro(
            'withCorrelationId',
            fn () => $this->withHeaders(['X-Correlation-ID' => Auditor::correlationId()])
        );
    }

    private function extendBusDispatcher()
    {
        if (config('butler.audit.extend_bus_dispatcher') === false) {
            return;
        }

        $this->app->extend(
            BaseBusDispatcher::class,
            fn ($dispatcher, $app) => new BusDispatcher($app, $dispatcher)
        );
    }

    public function listenForJobProcessedEvent()
    {
        if ($this->app->runningInConsole()) {
            Queue::after(fn (JobProcessed $event) => Auditor::correlationId(null));
        }
    }
}
