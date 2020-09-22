<?php

declare(strict_types=1);

namespace App\Command;

use App\Log;
use App\Redis;
use Carbon\Carbon;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Guzzle\HandlerStackFactory;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Hyperf\DbConnection\Db;
use Hyperf\Guzzle\RetryMiddleware;

/**
 * @Command
 */
class ImpressionCommand extends HyperfCommand
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
        parent::__construct('forward:impression');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Forward Impression');
    }

    public function handle()
    {
        // $redis = $this->container->get(RedisFactory::class)->get('default');
        $this->redis = Redis::get('default');
        // $this->logger = $this->container->get(LoggerFactory::class)->get('log', 'default');
        $this->logger = Log::get('log', 'default');
        //guzzle协程客户端
        // $options = [];
        // $this->client = $this->container->get(ClientFactory::class)->create($options);
        //guzzle协程客户端连接池
        $options = [
            'min_connections' => 10,
            'max_connections' => 100,
            'wait_timeout' => 3.0,
            'max_idle_time' => 60,
        ];
        $middlewares = [
            'retry' => [RetryMiddleware::class, [2, 60]],
        ];
        $factory = new HandlerStackFactory();
        $stack = $factory->create($options, $middlewares);
        $this->client = make(Client::class, [
                'config' => [
                    'handler' => $stack,
                ],
            ]);

        $impressionLists = ['impression_list_queue'];
        //消费消息
        $n =0;
        while (true) {
            try {
                $msg = $this->redis->brpop(
                    $impressionLists,
                    0
                );
                if ($msg[1]) { //业务处理
                    $promise = $this->client->getAsync($msg[1]);
                    // $promise->then(
                    //     function (ResponseInterface $res) {
                    //         echo $res->getStatusCode() . "\n";
                    //     },
                    //     function (RequestException $e) use ($msg){
                    //         $this->logger->error($e);
                    //         $this->logger->error($msg);
                    //     }
                    // );
                    $response = $promise->wait();
                    // $body = $response->getBody();
                    // $stringBody = (string) $body;
                    // var_dump($stringBody);
                    // $headers = $response->getHeaders();
                    // var_dump(\Hyperf\Utils\Coroutine::inCoroutine());
                    // $n++;
                    // $this->logger->info($n.PHP_EOL.'list:  '.$msg[0]. PHP_EOL . 'url:  '.$msg[1]);
                    // $this->logger->info($n);
                    if (strpos($msg[1], 'app') !== false)
                    {
                        Db::table('log_impressions')->insert([
                            'list' => $msg[0],
                            'url' => $msg[1],
                            'exception' => '',
                            'created_at' => Carbon::now(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Db::table('log_impressions')->insert([
                    'list' => $msg[0],
                    'url' => $msg[1],
                    'exception' => $e,
                    'created_at' => Carbon::now(),
                ]);
                $this->logger->error($e);
            }
        }
    }
}
