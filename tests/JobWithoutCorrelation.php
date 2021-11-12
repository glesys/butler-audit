<?php

namespace Butler\Audit\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class JobWithoutCorrelation implements ShouldQueue
{
    use Queueable;

    public function handle()
    {
        return true;
    }
}
