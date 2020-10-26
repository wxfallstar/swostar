<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/4/21
 * Time: 20:31
 */

namespace SwoStar\Server\Websocket;


class Connections
{
    protected static $connections = [];

    public static function init($fd, $request){
        self::$connections[$fd]['path'] = $request->server['path_info'];
        self::$connections[$fd]['request'] = $request;
    }

    public static function get($fd){
        return self::$connections[$fd];
    }

    public static function del($fd){
        unset(self::$connections[$fd]);
    }
}