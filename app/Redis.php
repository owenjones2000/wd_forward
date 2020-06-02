<?php

declare(strict_types=1);

namespace App;

use Hyperf\Logger\Logger;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;

class Redis
{
    public static function get($name = 'default')
    {
        return ApplicationContext::getContainer()->get(RedisFactory::class)->get($name);
    }
}
