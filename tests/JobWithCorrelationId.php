<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Bus\WithCorrelationId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class JobWithCorrelationId implements ShouldQueue
{
    use Queueable;
    use WithCorrelationId;

    public function handle()
    {
        return true;
    }
}
