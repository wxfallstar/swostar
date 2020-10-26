<?php

/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/4/12
 * Time: 12:35
 */
namespace SwoStar\Config;
class Config
{
    protected $configPath;

    protected $items = [];

    public function __construct(){
        $this->configPath = app()->getBasePath().'/config';

        $this->items = $this->phpParser();
    }

    //.php文件的配置
    public function phpParser(){
        // 1. 找到文件
        // 此处跳过多级的情况
        $files = scandir($this->configPath);
        $data = null;
        // 2. 读取文件信息
        foreach ($files as $key => $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            // 2.1 获取文件名
            $filename = \stristr($file, ".php", true);
            // 2.2 读取文件信息
            $data[$filename] = include $this->configPath."/".$file;
        }

        // 3. 返回
        return $data;
    }

    // key.key2.key3
    public function get($keys)
    {
        $data = $this->items;
        foreach (\explode('.', $keys) as $key => $value) {
            $data = $data[$value];
        }
        return $data;
    }

}