<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/demo/payment-failed', function () {
    Log::channel('data-prepper')->error('enyyyy payment failed', [
        'user_id' => 68,
        'order_id' => 78,
        'service' => 'payment-service-engy',
    ]);

    return response()->json([
        'queue' => config('dataprepper.queue'),
        'message' => 'Logggggggg queued for index laravel-logs.',
    ]);
});
