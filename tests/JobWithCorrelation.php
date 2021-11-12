<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Bus\WithCorrelation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class JobWithCorrelation implements ShouldQueue
{
    use Queueable;
    use WithCorrelation;

    public function handle()
    {
        return true;
    }
}
