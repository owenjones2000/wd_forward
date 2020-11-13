<?php

declare(strict_types=1);

namespace App\Command;

use App\Log;
use App\Model\SkillzMail;
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
use Ramsey\Uuid\Uuid;

/**
 * @Command
 */
class SkillzMailCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    protected $email_link = 'http://photo.luckfun.vip/questionnaire/first';

    protected $url = 'http://g.luckymoney.vip/send_email';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('skillz:mail');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('skillz mail send');
    }

    public function handle()
    {
        $this->redis = Redis::get('default');
        $this->logger = Log::get('log', 'default');
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

        //消费消息
        $n = 0;
        try {
            $emails = SkillzMail::query()->where('send', 0)->orderBy('id', 'desc')
            // ->limit(1)
            ->get();
            
            foreach ($emails as $key => $skill) {
                $email = $skill->email;
                # code...
                $user_info = [
                    'address' => $email ?? null
                ];
                $user_info_b64 = base64_encode(json_encode($user_info));
                $tag["link"] = $this->email_link . '?user_token=' . urlencode($user_info_b64); //
                // $time = Carbon::now()->format('Y-m-d H:i:s');
                // $time = '2020-11-12 17:30:00';
                $time = null;
                $res = $this->notifyMailQueueV2(
                    'com.game.blackjack21blitz',
                    [$email],
                    'questionnaire_first',
                    $tag,
                    $time
                );
                var_dump($email);
                var_dump($res);
                if ($res) {
                    $skill->send = 1;
                    $skill->save();
                }
            }
   
       
                // $promise->then(
                //     function (ResponseInterface $res) {
                //         echo $res->getStatusCode() . "\n";
                //     },
                //     function (RequestException $e) use ($msg){
                //         $this->logger->error($e);
                //         $this->logger->error($msg);
                //     }
                // );
                // $body = $response->getBody();
                // $stringBody = (string) $body;
                // var_dump($stringBody);
                // $headers = $response->getHeaders();
                // var_dump(\Hyperf\Utils\Coroutine::inCoroutine());
                // $n++;
                // $this->logger->info($n.PHP_EOL.'list:  '.$msg[0]. PHP_EOL . 'url:  '.$msg[1]);
                // $this->logger->info($n);
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    public function notifyMailQueueV2($bundle_id, $cash_emails, $feature = 'cash_success', $tags = null, $time = null)
    {
        try {
            $params = [
                'bundle_id' => $bundle_id,
                'list' => implode(',', $cash_emails),
                'task_id' => md5(Uuid::uuid1()->getHex() . $bundle_id),
                'feature' => $feature
            ];
            if ($tags) {
                $params['tags'] = json_encode($tags);
            }
            if ($time) {
                $params['send_at'] = $time;
            }
            // $result = Curl::post(self::$url, null, $params);
            $promise = $this->client->requestAsync('post', $this->url, [
                'json' => $params,
            ]);
            $result = $promise->wait()->getBody();
            $stringBody = (string) $result;
            var_dump($stringBody);
            $data = json_decode($stringBody, true);
            if (isset($data['code']) && $data['code'] == 0 && isset($data['data']) && $data['data'] > 0) {
                $this->logger->info('notifyMailQueue success');
                return true;
            } else {
                $this->logger->info('邮件系统响应失败：' . $result);
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            return false;
        }
    }
}
