<?php

namespace App\Task;

use App\Redis;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\Inject;

class ListLen
{

    /**
     * @Inject()
     * @var \Hyperf\Contract\StdoutLoggerInterface
     */
    private $logger;

    public function execute()
    {
        $redis = Redis::get();
        $len = $redis->llen('impression_list_queue');
        $this->logger->info('impression_list_len: '.$len);
    }
}
