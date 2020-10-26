<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/4/2
 * Time: 22:05
 */
namespace SwoStar\Server;
use Swoole\Coroutine\Http\Client;
use Swoole\Server as SwooleServer;
use SwoStar\Foundation\Application;
use SwoStar\RPC\Rpc;
use SwoStar\Supper\Inotify;

/**
 * 所有服务的父类，主要包含公共操作
 * Class Server
 * @package SwoStar\Server
 */
abstract class Server{
    protected $mod = SWOOLE_PROCESS;
    protected $sock_type = SWOOLE_SOCK_TCP;

    /**
     * swostar server
     * @var Server|HttpServer|WebSocketServer|
     */
    protected $swooleServer;

    protected $host = '0.0.0.0';

    protected $port = 9000;

    protected $app;

    protected $redis;
    /**
     * @var SwoStar/Support/Inotify
     */
    protected $inotify = null;
    /**
     * 是否开启文件检测
     * @var bool
     */
    protected $watchFile = false;

    /**
     * 用于记录系统pid的信息
     * @var string
     */
    protected $pidFile = "/runtime/swostar.pid";
    /**
     * 用于记录pid的信息
     * @var array
     */
    protected $pidMap = [
        'masterPid'  => 0,
        'managerPid' => 0,
        'workerPids' => [],
        'taskPids'   => []
    ];

    /**
     * 注册的回调事件
     * [
     *   // 这是所有服务均会注册的时间
     *   "server" => [],
     *   // 子类的服务
     *   "sub" => [],
     *   // 额外扩展的回调函数
     *   "ext" => []
     * ]
     *
     * @var array
     */
    protected $event = [
        // 这是所有服务均会注册的时间
        "server" => [
            // 事件   =》 事件函数
            "start"        => "onStart",
            "managerStart" => "onManagerStart",
            "managerStop"  => "onManagerStop",
            "shutdown"     => "onShutdown",
            "workerStart"  => "onWorkerStart",
            "workerStop"   => "onWorkerStop",
            "workerError"  => "onWorkerError",
        ],
        // 子类的服务
        "sub" => [],
        // 额外扩展的回调函数
        // 如 ontart等
        "ext" => []
    ];

    /**
     * swoole的相关配置信息
     * @var array
     */
    protected $config = [
        'task_worker_num' => 0,
    ];

    public function __construct(Application $app){
        $this->app = $app;

        $this->initSetting();

        //1、创建swoole server服务
        $this->createServer();

        //3、设置需要注册的回调函数
        $this->initEvent();
        //4、设置swoole的回调函数
        $this->setSwooleEvent();
    }

    /**
     * 通过http协程客户端发送websocket消息
     * @param $ip
     * @param $port
     * @param $data
     * @param null $header
     */
    public function send($ip, $port, $data, $header = null){
        $cli = new Client($ip, $port);
        empty($header) ?: $cli->setHeaders($header);
        if($cli->upgrade('/')){
            $cli->push(\json_encode($data));
        }
    }

    /**
     * 创建服务
     */
    protected abstract function createServer();
    /**
     * 初始化监听的事件
     */
    protected abstract function initEvent();

    //通用方法

    public function start(){
        //2、设置配置信息
        $config = app('config');
        $this->swooleServer->set($this->config);
        if($config->get('server.rpc.tcpable')){
            new Rpc($this->swooleServer, $config->get('server.rpc'));
        }
        //5、启动服务
        $this->swooleServer->start();
    }

    protected abstract function initSetting();

    /**
     * 设置swoole的回调事件
     */
    protected function setSwooleEvent()
    {
        foreach ($this->event as $type => $events) {
            foreach ($events as $event => $func) {
                $this->swooleServer->on($event, [$this, $func]);
            }
        }
    }

    public function onStart(SwooleServer $server){
        $this->pidMap['masterPid'] = $server->master_pid;
        $this->pidMap['managerPid'] = $server->managerPid;

        $pidStr = \sprintf('%s,%s', $server->master_pid, $server->managerPid);
        //将pid保存到文件里面
        \file_put_contents(app()->getBasePath().$this->pidFile, $pidStr);
        if($this->watchFile){
            $this->inotify = new Inotify($this->app->getBasePath(), $this->watchEvent());
            $this->inotify->start();
        }
        //触发start事件监听进行服务注册register
        $this->app->make('event')->trigger('start', [$this]);
    }

    public function onManagerStart(SwooleServer $server){

    }

    public function onManagerStop(SwooleServer $server){

    }

    public function onShutdown(SwooleServer $server){

    }

    public function onWorkerStart(SwooleServer $server, int $worker_id){
        $this->pidMap['workerPids'] = [
            'id'  => $worker_id,
            'pid' => $server->worker_id
        ];
        $this->redis = new \Redis();
        $this->redis->pconnect($this->app->make('config')->get('database.redis.host'),
            $this->app->make('config')->get('database.redis.port'));
    }

    public function onWorkerStop(){

    }

    public function onWorkerError(){

    }

    protected function watchEvent()
    {
        return function($event){
            $action = 'file:';
            switch ($event['mask']) {
                case IN_CREATE:
                    $action = 'IN_CREATE';
                    break;

                case IN_DELETE:
                    $action = 'IN_DELETE';
                    break;
                case \IN_MODIFY:
                    $action = 'IN_MODIF';
                    break;
                case \IN_MOVE:
                    $action = 'IN_MOVE';
                    break;
            }
            $this->swooleServer->reload();
        };
    }

    /**
     * @param array
     *
     * @return static
     */
    public function setEvent($type, $event)
    {
        // 暂时不支持直接设置系统的回调事件
        if ($type == "server") {
            return $this;
        }
        $this->event[$type] = $event;
        return $this;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = array_map($this->config, $config);
        return $this;
    }

    /**
     * @param bool $watchFile
     */
    public function setWatchFile(bool $watchFile)
    {
        $this->watchFile = $watchFile;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return \Redis
     */
    public function getRedis()
    {
        return $this->redis;
    }
}