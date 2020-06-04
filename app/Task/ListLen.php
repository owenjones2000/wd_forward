<?php

namespace App\Task;

use App\Log;
use App\Redis;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\Inject;

class ListLen
{

    public function execute()
    {
        $this->logger = Log::get('log');
        $redis = Redis::get();
        $len = $redis->llen('impression_list_queue');
        $this->logger->info('impression_list_len: '.$len);
    }
}
