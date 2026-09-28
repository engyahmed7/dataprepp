<?php

namespace Tests\Feature;

use App\Jobs\SendLogToDataPrepper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DataPrepperLoggingTest extends TestCase
{
    public function test_data_prepper_channel_dispatches_queued_job(): void
    {
        Queue::fake();

        Log::channel('data-prepper')->error('Payment failed', [
            'user_id' => 123,
            'order_id' => 456,
            'service' => 'payment-service',
        ]);

        Queue::assertPushedOn(config('dataprepper.queue'), SendLogToDataPrepper::class, function (SendLogToDataPrepper $job): bool {
            return $job->payload['message'] === 'Payment failed'
                && $job->payload['user_id'] === 123
                && $job->payload['level'] === 'ERROR';
        });
    }

    public function test_job_posts_json_array_to_data_prepper(): void
    {
        Http::fake([
            'http://127.0.0.1:2021/laravel/logs' => Http::response('200 OK', 200),
        ]);

        $payload = [
            'level' => 'ERROR',
            'message' => 'Payment failed',
            'user_id' => 123,
        ];

        (new SendLogToDataPrepper($payload))->handle();

        Http::assertSent(function ($request) use ($payload): bool {
            return $request->url() === 'http://127.0.0.1:2021/laravel/logs'
                && $request['0']['message'] === $payload['message'];
        });
    }

    public function test_demo_route_queues_log_event(): void
    {
        Queue::fake();

        $this->getJson('/demo/payment-failed')
            ->assertOk()
            ->assertJsonPath('queued', true)
            ->assertJsonPath('queue', config('dataprepper.queue'));

        Queue::assertPushed(SendLogToDataPrepper::class);
    }
}
