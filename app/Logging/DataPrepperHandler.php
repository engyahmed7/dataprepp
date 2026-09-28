<?php

namespace App\Logging;

use App\Jobs\SendLogToDataPrepper;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class DataPrepperHandler extends AbstractProcessingHandler
{
    public function __construct(int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $payload = array_merge([
            'level' => $record->level->getName(),
            'message' => $record->message,
            'channel' => $record->channel,
            'datetime' => $record->datetime->format(DATE_ATOM),
            'service' => config('app.name'),
            'environment' => config('app.env'),
        ], $record->context);

        SendLogToDataPrepper::dispatch($payload);
    }
}
