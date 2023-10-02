<?php
declare(strict_types=1);

namespace Zgtec\ElasticLog\Facades;

use Illuminate\Support\Facades\Facade;
use Zgtec\ElasticLog\ElasticLog as Model;

class ElasticLog extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return Model::class;
    }
}
