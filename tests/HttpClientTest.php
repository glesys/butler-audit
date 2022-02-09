<?php

namespace Butler\Audit\Tests\Unit;

use Butler\Audit\Facades\Auditor;
use Butler\Audit\Tests\AbstractTestCase;
use Illuminate\Support\Facades\Http;

class HttpClientTest extends AbstractTestCase
{
    public function test_macro_withCorrelation_adds_header_with_correlation_id()
    {
        Http::fake();
        Http::withCorrelation()->post('/api');
        Http::assertSent(fn ($request)
            => $request->hasHeader('X-Correlation-ID')
            && str($request->header('X-Correlation-ID')[0])->isUuid());
    }

    public function test_macro_withCorrelation_adds_header_with_correlation_trail_if_set()
    {
        Auditor::correlationTrail('trail');

        Http::fake();
        Http::withCorrelation()->post('/api');
        Http::assertSent(fn ($request) => $request->hasHeader('X-Correlation-Trail', 'trail'));
    }
}
