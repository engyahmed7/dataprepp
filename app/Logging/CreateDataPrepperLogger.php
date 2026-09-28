<?php

namespace App\Logging;

use Monolog\Logger;

class CreateDataPrepperLogger
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __invoke(array $config): Logger
    {
        return new Logger('data-prepper', [
            new DataPrepperHandler($config['level'] ?? 'debug'),
        ]);
    }
}
