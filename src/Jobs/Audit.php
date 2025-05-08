<?php

namespace Butler\Audit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Audit implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public $backoff = 16;

    public function __construct(public array $data) {}

    public function handle()
    {
        if (config('butler.audit.driver') === 'log') {
            Log::info(json_encode($this->data));

            return;
        }

        try {
            Http::withToken(config('butler.audit.token'))
                ->acceptJson()
                ->post(config('butler.audit.url'), $this->data)
                ->throw();
        } catch (RequestException $exception) {
            Log::error($exception->getMessage(), [
                'requestData' => $this->data,
                'responseBody' => $exception->response->body(),
            ]);

            throw $exception;
        }
    }
}
