<?php

use ESD\Plugins\Aop\AopConfig;
use ESD\Plugins\Aop\AopPlugin;
use ESD\Plugins\Consul\ConsulPlugin;
use ESD\Server\Co\ExampleClass\DefaultServer;

require __DIR__ . '/../vendor/autoload.php';

define("ROOT_DIR", __DIR__ . "/..");
define("RES_DIR",__DIR__."/resources");

$server = new DefaultServer();
$server->getPlugManager()->addPlug(new ConsulPlugin());
$server->getPlugManager()->addPlug(new AopPlugin(new AopConfig(__DIR__ . "/../exampleClass")));
//配置
$server->configure();
//启动
$server->start();
