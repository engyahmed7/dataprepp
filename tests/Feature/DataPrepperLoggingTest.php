<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DataPrepperLoggingTest extends TestCase
{
    public function test_demo_route_writes_json_log_without_queueing(): void
    {
        $path = storage_path('framework/testing/api-access-test-'.uniqid().'.log');

        config(['logging.channels.api-access.path' => $path]);
        Log::forgetChannel('api-access');
        Queue::fake();

        $this->getJson('/demo/payment-failed')
            ->assertOk()
            ->assertJsonPath('logged', true);

        $files = glob(dirname($path).'/'.pathinfo($path, PATHINFO_FILENAME).'-*.log');

        $this->assertNotFalse($files);
        $this->assertCount(1, $files);

        $record = json_decode(file_get_contents($files[0]), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Payment failed', $record['message']);
        Queue::assertNothingPushed();

        unlink($files[0]);
    }
}
