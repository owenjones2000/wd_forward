<?php

declare(strict_types=1);

namespace App\Command;

use App\Log;
use App\Redis;
use Carbon\Carbon;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Hyperf\Guzzle\ClientFactory;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Guzzle\RetryMiddleware;

/**
 * @Command
 */
class NetworkCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;


    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('forward:network');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Forward Network');
    }

    public function handle()
    {
        $this->redis = Redis::get('network');
        $this->logger = Log::get('log', 'default');
        //guzzle协程客户端
        // $options = [];
        // $this->client = $this->container->get(ClientFactory::class)->create($options);
        //guzzle协程客户端连接池

        $networkLists = ['network_list_queue'];
        //消费消息
        $insData = [];
        while (true) {
            try {
                $msg = $this->redis->brpop(
                    $networkLists,
                    0
                );
                var_dump($msg);
                $this->logger->info($msg);
                if ($msg[1]) { //业务处理
                    $data = json_decode($msg[1], true);
                    $insData[] = $data;
                    // $table_name = 'z_networks_' . Carbon::now('UTC')->format('Ymd');
                    // $intRes = Db::table($table_name)->insert($data);

                    // var_dump($intRes);
                    // $this->logger->info(PHP_EOL.'list:  '.$msg[0]. PHP_EOL . 'data:  '.$msg[1]);
                }
                if (count($insData) >= 100) {
                    # code...
                    $table_name = 'z_networks_' . Carbon::now('UTC')->format('Ymd');
                    $intRes = Db::table($table_name)->insert($insData);
                    if ($intRes){
                        $insData = [];
                    }
                }
                
                
            } catch (\Exception $e) {
                
                $this->logger->error($e);
            }
        }
    }
}
