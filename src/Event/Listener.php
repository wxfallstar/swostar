<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/6/25
 * Time: 17:42
 */

namespace SwoStar\Event;
use SwoStar\Foundation\Application;

/**
 * 事件监听抽象类
 * Class Listener
 * @package SwoStar\Event
 */
abstract class Listener
{
    protected $name = 'listener';

    protected $app ;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public abstract function handler();

    public function __construct(Application $app = null){
        $this->app = $app;
    }
}