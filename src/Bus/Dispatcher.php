<?php

namespace Butler\Audit\Bus;

use Butler\Audit\Facades\Auditor;
use Illuminate\Bus\Dispatcher as BaseDispatcher;
use Illuminate\Contracts\Container\Container;

class Dispatcher extends BaseDispatcher
{
    public function __construct(Container $app, BaseDispatcher $dispatcher)
    {
        parent::__construct($app, $dispatcher->queueResolver);
    }

    public function dispatchToQueue($command)
    {
        if (in_array(WithCorrelation::class, class_uses_recursive($command))) {
            $command->correlationId = Auditor::correlationId();
            $command->correlationTrail = Auditor::correlationTrail();
        }

        return parent::dispatchToQueue($command);
    }
}
