<?php

namespace App\Logging;

use Illuminate\Support\Facades\Auth;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class AddAuthenticatedUser implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        // try {
        //     $user = '1';
        // } catch (\Throwable) {
        //     return $record;
        // }

        // if (!$user) {
        //     return $record;
        // }

        return $record->with(
            extra: array_merge(
                $record->extra,
                [
                    'authenticated_user' => [
                        'id' =>4,
                    ],
                ]
            )
        );
    }
}
