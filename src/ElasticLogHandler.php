<?php
declare(strict_types=1);

namespace Zgtec\ElasticLog;

use Illuminate\Support\Arr;
use Monolog\Handler\ElasticsearchHandler;
use Monolog\LogRecord;
use Zgtec\ElasticLog\Facades\ElasticLog;

class ElasticLogHandler extends ElasticsearchHandler
{
    protected function write(LogRecord|array $record): void
    {
        $formatted = $record->formatted;
        ElasticLog::create([
            'type' => Arr::get($formatted['context'], 'type', 'Default'),
            'uri' => Arr::get($formatted['context'], 'uri', ''),
            'user' => Arr::get($formatted['context'], 'user', ''),
            'message' => $formatted['message'],
            'context' => json_encode($formatted['context']),
            'logging_time' => date('Y-m-d H:i:s', strtotime($formatted['datetime'])),
            'logging_month' => date('Ym', strtotime($formatted['datetime'])),
            'device' => hash('sha512', request()->ip() . request()->header('User-Agent'))
        ]);
    }

}
