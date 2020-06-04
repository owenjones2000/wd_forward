<?php

use Hyperf\Crontab\Crontab;

return [
    // 是否开启定时任务
    'enable' => true,
    'crontab' => [
        (new Crontab())->setName('impression_list_len')->setRule('*/10 * * * * *')->setCallback([App\Task\ListLen::class, 'execute']),
    ]
];
