<?php

declare(strict_types=1);

namespace App;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;

class Config
{
    public static function get()
    {
        return ApplicationContext::getContainer()->get(ConfigInterface::class);
    }
}
