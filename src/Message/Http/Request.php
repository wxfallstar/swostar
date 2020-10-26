<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/4/9
 * Time: 22:16
 */
namespace SwoStar\Message\Http;
use Swoole\Http\Request as SwooleRequest;
class Request{
    protected $method;

    protected $uriPath;

    protected $swooleRequest;

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return mixed
     */
    public function getUriPath()
    {
        return $this->uriPath;
    }

    public static function init(SwooleRequest $request){
        //从容器中获取避免重复创建对象
        $self = app('httpRequest');
        $self->swooleRequest = $request;
        $self->server = $request->server;
        $self->method = $request->server['request_method'] ? : '';
        $self->uriPath = $request->server['request_uri'] ? : '';
        return $self;
    }
}