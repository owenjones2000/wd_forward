<?php

declare(strict_types=1);

namespace App;

use Hyperf\Logger\Logger;
use Hyperf\Utils\ApplicationContext;

class Log
{
    public static function get($name = 'hyperf', $group = 'default')
    {
        return ApplicationContext::getContainer()->get(\Hyperf\Logger\LoggerFactory::class)->get($name, $group);
    }
}
