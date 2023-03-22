<?php

namespace Butler\Audit\Tests;

use Butler\Audit\Jobs\Audit as AuditJob;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;

class JobTest extends AbstractTestCase
{
    public function test_it_sends_http_request_correctly()
    {
        Http::fake();

        (new AuditJob(['foo' => 'bar']))->handle();

        Http::assertSent(fn ($request)
            => $request->url() === config('butler.audit.url')
            && $request->hasHeader('Authorization', 'Bearer ' . config('butler.audit.token'))
            && $request->hasHeader('Accept', 'application/json')
            && $request['foo'] === 'bar');
    }

    public function test_it_throws_exception_on_http_error()
    {
        Http::fake(fn () => Http::response('Unauthorized', 401));

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('HTTP request returned status code 401');

        Log::shouldReceive('error')
            ->once()
            ->with(Mockery::pattern('/^HTTP request returned status code 401/'), [
                'requestData' => ['foo' => 'bar'],
                'responseBody' => 'Unauthorized',
            ]);

        (new AuditJob(['foo' => 'bar']))->handle();
    }

    public function test_it_logs_correctly_when_driver_is_set_to_log()
    {
        Http::fake();

        config(['butler.audit.driver' => 'log']);

        Log::shouldReceive('info')->once()->with(json_encode(['foo' => 'bar']));

        (new AuditJob(['foo' => 'bar']))->handle();

        Http::assertNothingSent();
    }
}
