<?php

namespace Butler\Audit\Tests\Unit;

use Butler\Audit\Tests\AbstractTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HttpClientTest extends AbstractTestCase
{
    public function test_macro_withCorrelationId_adds_header_with_correlation_id_from_container()
    {
        Http::fake();
        Http::withCorrelationId()->post('/api');
        Http::assertSent(fn ($request)
            => $request->hasHeader('X-Correlation-ID')
            && Str::isUuid($request->header('X-Correlation-ID')[0]));
    }
}
