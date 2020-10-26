<?php

/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/4/19
 * Time: 16:04
 */
namespace SwoStar\Server\Websocket;
use Swoole\Http\Request;
use Swoole\Http\Response;
use SwoStar\Console\Input;
use SwoStar\Server\Http\HttpServer;
use Swoole\WebSocket\Server as SwooleServer;

class WebSocketServer extends HttpServer
{
    public function createServer(){
        $this->swooleServer = new SwooleServer($this->host, $this->port);
        Input::info('websocket server 访问：ws://192.168.174.169:'.$this->port);
    }

    protected function initSetting(){
        $config = app('config');
        $this->port = $config->get('server.ws.port');
        $this->host = $config->get('server.ws.host');
        $this->config = $config->get('server.ws.swoole');
    }

    public function initEvent(){
        $event = [
            'request' => 'onRequest',
            'open' => "onOpen",
            'message' => "onMessage",
            'close' => "onClose",
        ];
        // 判断是否自定义握手的过程
        ( ! $this->app->make('config')->get('server.ws.is_handshake'))?: $event['handshake'] = 'onHandShake';

        $this->setEvent('sub', $event);
    }

    public function onHandShake(Request $request, Response $response){
        $this->app->make('event')->trigger('ws.handshake', [$this, $request, $response]);

        //因为设置了onHandShake回调函数，就不会触发onOpen
        $this->onOpen($this->swooleServer, $request);
    }

    public function onOpen(SwooleServer $server, $request) {
        //dd($request->server['path_info']);
        Connections::init($request->fd, $request);

        $return = app('route')->setFlag('WebSocket')->setMethod('open')->match($request->server['path_info'], [$server, $request]);
    }

    public function onMessage(SwooleServer $server, $frame) {
        //管理连接的对象保存连接的fd
        $path = (Connections::get($frame->fd))['path'];

        $this->app->make('event')->trigger('ws.message.front', [$this, $server, $frame]);

        //消息的业务流程
        $return = app('route')->setFlag('WebSocket')->setMethod('message')->match($path, [$server, $frame]);

    }

    public function onClose($server, $fd) {
        $path = (Connections::get($fd))['path'];

        $return = app('route')->setFlag('WebSocket')->setMethod('close')->match($path, [$server, $fd]);

        $this->app->make('event')->trigger('ws.close', [$this, $server, $fd]);

        Connections::del($fd);
    }

    public function sendAll($msg){
        //通过Server::$connections获取当前服务的所有连接
        foreach ($this->swooleServer->connections as $fd){
            if($this->swooleServer->exist($fd)){
                $this->swooleServer->push($fd, $msg);
            }
        }
    }
}