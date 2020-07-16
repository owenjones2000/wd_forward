<?php

namespace App\Task;

use App\HttpClient;
use App\Log;
use App\Redis;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class ImpressionRetry
{

    public function execute()
    {
        $this->logger->info('ImpressionRetry: ');
        $this->logger = Log::get('log');
        $impressions = Db::table('log_impressions')->all();
        $this->client = HttpClient::get();
        try{
            foreach ($impressions as $key => $impression) {
                $promise = $this->client->getAsync($impression['url']);
                $response = $promise->wait();
                Db::table('log_impressions')->where('id', $impression['id'])->delete();
            }
        } catch (\Exception $e) {
            $this->logger->info($e);
        }
        
    }
}
