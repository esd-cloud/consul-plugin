<?php

use ESD\Core\Server\Config\PortConfig;
use ESD\Core\Server\Config\ServerConfig;
use ESD\Plugins\Aop\AopConfig;
use ESD\Plugins\Aop\AopPlugin;
use ESD\Plugins\Consul\Config\ConsulConfig;
use ESD\Plugins\Consul\ConsulPlugin;
use ESD\Plugins\Consul\ExampleClass\ConsulPort;
use ESD\Server\Co\ExampleClass\DefaultServer;

require __DIR__ . '/../vendor/autoload.php';


//----多端口配置----
$httpPortConfig = new PortConfig();
$httpPortConfig->setHost("0.0.0.0");
$httpPortConfig->setPort(8080);
$httpPortConfig->setSockType(PortConfig::SWOOLE_SOCK_TCP);
$httpPortConfig->setOpenHttpProtocol(true);

//---服务器配置---
$serverConfig = new ServerConfig();
$serverConfig->setWorkerNum(1);
$serverConfig->setRootDir(__DIR__ . "/../");

$server = new DefaultServer($serverConfig);
//添加端口
$server->addPort("http", $httpPortConfig,ConsulPort::class);
//添加插件
$consulConfig = new ConsulConfig("http://192.168.1.200:8500");
$consulConfig->setLeaderName("Test");
$server->getPlugManager()->addPlug(new ConsulPlugin($consulConfig));
$server->getPlugManager()->addPlug(new AopPlugin(new AopConfig(__DIR__ . "/../exampleClass")));
$server->addProcess("test1");
//配置
$server->configure();
//configure后可以获取实例
$httpPort = $server->getPortManager()->getPortFromName("http");
//启动
$server->start();
