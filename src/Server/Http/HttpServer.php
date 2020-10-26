<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/4/2
 * Time: 22:03
 */
namespace SwoStar\Server\Http;
use SwoStar\Console\Input;
use SwoStar\Message\Http\Request as HttpRequest;
use SwoStar\Server\Server;
use Swoole\Http\Server as SwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

class HttpServer extends Server{
    public function createServer(){
        $this->swooleServer = new SwooleServer($this->host, $this->port);
        Input::info('http server 访问：http://192.168.174.169:'.$this->port);
    }

    protected function initSetting(){
        $config = app('config');
        $this->host = $config->get('server.http.host');
        $this->port = $config->get('server.http.port');
        $this->config = $config->get('server.http.swoole');
    }

    public function initEvent(){
        $this->setEvent('sub', [
            'request'=>'onRequest'
        ]);
    }

    public function onRequest(SwooleRequest $request, SwooleResponse $response){
        $uri = $request->server['request_uri'];
        if ($uri == '/favicon.ico') {
            $response->status(404);
            $response->end();
            return null;
        }
        $httpRequest = HttpRequest::init($request);
        //dd($httpRequest->getMethod(), 'Method');
        //dd($httpRequest->getUriPath(), 'UriPath');
        // 执行控制器的方法
        $return = app('route')->setFlag('Http')->setMethod($httpRequest->getMethod())->match($httpRequest->getUriPath());
        $response->end($return);
    }
}