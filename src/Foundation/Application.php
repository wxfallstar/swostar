<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/4/2
 * Time: 22:04
 */
namespace SwoStar\Foundation;
use SwoStar\Container\Container;
use SwoStar\Event\Event;
use SwoStar\Routes\Route;
use SwoStar\Server\Http\HttpServer;
use SwoStar\Server\Websocket\WebSocketServer;

class Application extends Container
{
    protected const SWOSTAR_WELCOME = "
      _____                     _____     ___
     /  __/             ____   /  __/  __/  /__   ___ __    __  __
     \__ \  | | /| / / / __ \  \__ \  /_   ___/  /  _`  |  |  \/ /
     __/ /  | |/ |/ / / /_/ /  __/ /   /  /_    |  (_|  |  |   _/
    /___/   |__/\__/  \____/  /___/    \___/     \___/\_|  |__|
    ";

    protected $basePath;

    public function __construct($path = null){
        if(!empty($path)){
            $this->setBasePath($path);
        }
        $this->registerBaseBindings();
        $this->init();
        dd(self::SWOSTAR_WELCOME, '启动项目');
        //echo self::SWOSTAR_WELCOME."\n";
    }

    public function init()
    {
        $this->bind('route', Route::getInstance()->registerRoute());
        $this->bind('event', $this->registerEvent());
        //dd(app('route')->getRoutes());
        //dd($this->make('event')->getEvents());
    }

    /**
     * 注册框架事件
     * @return Event
     */
    public function registerEvent(){
        $event = new Event();

        $files = scandir($this->getBasePath().'/app/Listener');
        // 2. 读取文件信息
        foreach ($files as $key => $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $class = 'App\\Listener\\'.\explode('.', $file)[0];
            if(\class_exists($class)){
                $listener = new $class($this);
                $event->register($listener->getName(), [$listener, 'handler']);
            }
        }

        return $event;
    }


    public function run($argv){
        $server = null;
        switch ($argv[1]){
            case 'http:start':
                $server = new HttpServer($this);
                break;
            case 'ws:start':
                $server = new WebSocketServer($this);
                break;
            default:
                $server = new HttpServer($this);
                break;
        }
        $server->setWatchFile(true);
        $server->start();
    }

    public function registerBaseBindings(){
        self::setInstance($this);
        $binds = [
            //标识  => 对象
            'config' => (new \SwoStar\Config\Config()),
            'index' => (new \SwoStar\Index()),
            'httpRequest' => (new \SwoStar\Message\Http\Request()),
        ];
        foreach ($binds as $key=>$value){
            $this->bind($key, $value);
        }
    }

    /**
     * @return mixed
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param mixed $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = \rtrim($basePath, '\/');
    }

}