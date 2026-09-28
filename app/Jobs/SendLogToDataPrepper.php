<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendLogToDataPrepper implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [1, 5, 15, 30, 60];

    public int $timeout = 15;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload)
    {
        $this->onQueue(config('dataprepper.queue', 'logs'));
    }

    public function handle(): void
    {
        $response = Http::timeout(config('dataprepper.timeout'))
            ->acceptJson()
            ->asJson()
            ->post(config('dataprepper.url'), [$this->payload]);

        $response->throw();
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('single')->error('Failed to send log to Data Prepper after retries.', [
            'message' => $exception?->getMessage(),
            'payload' => $this->payload,
        ]);
    }
}
