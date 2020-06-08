<?php

declare(strict_types=1);

namespace App;

use Hyperf\Guzzle\HandlerStackFactory;
use Hyperf\Guzzle\RetryMiddleware;
use Hyperf\Utils\ApplicationContext;

/**
 * guzzle协程客户端连接池
 */
class HttpClient
{

    public static function get(array $option = [], array $middleware = [])
    {
        $options = [
            'min_connections' => 10,
            'max_connections' => 100,
            'wait_timeout' => 3.0,
            'max_idle_time' => 60,
        ];

        $middlewares = [
            'retry' => [RetryMiddleware::class, [2, 60]],
        ];
        $option = array_merge($options, $option);
        $middlewares = array_merge($middlewares, $middleware);
        $factory = new HandlerStackFactory();
        $stack = $factory->create($options, $middlewares);
 
        return make(Client::class, [
            'config' => [
                'handler' => $stack,
            ],
        ]);
    }
}
