<?php
use SwoStar\Console\Input;
use SwoStar\Foundation\Application;

/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/4/5
 * Time: 15:16
 */

if(!function_exists('app')){
    function app($a = null){
        if(empty($a)){
            return Application::getInstance();
        }
        return Application::getInstance()->make($a);
    }
}

if(!function_exists('dd')){
    function dd($message, $description = null){
        Input::info($message, $description);
    }
}
