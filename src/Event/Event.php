<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/6/25
 * Time: 17:31
 */

namespace SwoStar\Event;


/**
 * 事件的注册与触发
 * Class Event
 * @package SwoStar\Event
 */
class Event
{
    protected $events = [];

    /**
     * 事件注册
     * @param string $event 事件标识
     * @param \Closure $callback 回调函数
     */
    public function register($event, $callback){
        $event = \strtolower($event);

        $this->events[$event] = ['callback'=>$callback];
    }

    /**
     * 事件触发
     * @param string $event 事件标识
     * @param array $param 事件参数
     */
    public function trigger($event, $param = []){
        $event = \strtolower($event);
        if(isset($this->events[$event])){
            ($this->events[$event]['callback'])(...$param);
            dd('事件执行成功');
            return true;
        }
        dd('事件不存在');
    }

    /**
     * @param null $event
     * @return array
     */
    public function getEvents($event = null): array
    {
        return empty($event) ? $this->events : $this->events[$event];
    }
}