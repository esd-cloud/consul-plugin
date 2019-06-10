<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/24
 * Time: 14:42
 */

namespace ESD\Plugins\Consul;


use ESD\Core\Context\Context;
use ESD\Core\PlugIn\AbstractPlugin;
use ESD\Core\PlugIn\PluginInterfaceManager;
use ESD\Core\Plugins\Logger\GetLogger;
use ESD\Core\Server\Process\ProcessEvent;
use ESD\Plugins\Actuator\ActuatorPlugin;
use ESD\Plugins\Consul\Config\ConsulConfig;
use ESD\Plugins\Consul\Event\ConsulLeaderChangeEvent;
use ESD\Plugins\Consul\Event\ConsulServiceChangeEvent;
use ESD\Psr\Cloud\Leader;
use ESD\Psr\Cloud\Services;
use ESD\Server\Co\Server;

class ConsulPlugin extends AbstractPlugin
{
    use GetLogger;
    const processName = "helper";
    const processGroupName = "HelperGroup";

    /**
     * @var ConsulConfig
     */
    private $consulConfig;

    /**
     * @var Consul
     */
    private $consul;
    /**
     * @var ConsulLeader
     */
    private $leader;
    /**
     * @var ConsulServices
     */
    private $service;

    /**
     * ConsulPlugin constructor.
     * @param ConsulConfig $consulConfig
     * @throws \ReflectionException
     */
    public function __construct(ConsulConfig $consulConfig = null)
    {
        parent::__construct();
        //需要ActuatorPlugin的支持，所以放在ActuatorPlugin后加载
        $this->atAfter(ActuatorPlugin::class);
        if ($consulConfig == null) {
            $consulConfig = new ConsulConfig(null);
        }
        $this->consulConfig = $consulConfig;
    }

    /**
     * @param PluginInterfaceManager $pluginInterfaceManager
     * @return mixed|void
     * @throws \ESD\Core\Exception
     */
    public function onAdded(PluginInterfaceManager $pluginInterfaceManager)
    {
        parent::onAdded($pluginInterfaceManager);
        $actuatorPlugin = $pluginInterfaceManager->getPlug(ActuatorPlugin::class);
        if ($actuatorPlugin == null) {
            $actuatorPlugin = new ActuatorPlugin();
            $pluginInterfaceManager->addPlug($actuatorPlugin);
        }
    }

    /**
     * 获取插件名字
     * @return string
     */
    public function getName(): string
    {
        return "Consul";
    }

    /**
     * 在服务启动前
     * @param Context $context
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \ESD\Core\Plugins\Config\ConfigException
     * @throws \ReflectionException
     */
    public function beforeServerStart(Context $context)
    {
        //添加一个helper进程
        Server::$instance->addProcess(self::processName, HelperConsulProcess::class, self::processGroupName);
        //自动配置
        $this->consulConfig->autoConfig();
        $this->consulConfig->merge();
        //注入
        $this->leader = new ConsulLeader();
        $this->service = new ConsulServices();
        $this->setToDIContainer(Leader::class, $this->leader);
        $this->setToDIContainer(Services::class, $this->service);
    }

    /**
     * 在进程启动前
     * @param Context $context
     */
    public function beforeProcessStart(Context $context)
    {
        //每个进程监听Leader变更
        $call = Server::$instance->getEventDispatcher()->listen(ConsulLeaderChangeEvent::ConsulLeaderChangeEvent);
        $call->call(function (ConsulLeaderChangeEvent $event) {
            $leaderStatus = $event->isLeader() ? "true" : "false";
            $this->leader->setLeader($event->isLeader());
            $this->debug("收到Leader变更事件：$leaderStatus");
        });

        //每个进程监听Service变更
        $call = Server::$instance->getEventDispatcher()->listen(ConsulServiceChangeEvent::ConsulServiceChangeEvent);
        $call->call(function (ConsulServiceChangeEvent $event) {
            $this->debug("收到Service变更事件：{$event->getConsulServiceListInfo()->getServiceName()}");
            $this->service->modifyServices($event);
        });

        //Helper进程
        if (Server::$instance->getProcessManager()->getCurrentProcess()->getProcessName() === self::processName) {
            $this->consul = new Consul($this->consulConfig);
            //进程监听关服信息
            $call = Server::$instance->getEventDispatcher()->listen(ProcessEvent::ProcessStopEvent, null, true);
            $call->call(function () {
                //同步请求释放leader，关服操作无法使用协程
                $this->consul->releaseLeader(false);
                //同步请求注销service
                $this->consul->deregisterService(false);
            });
        }
        $this->ready();
    }

    /**
     * @return ConsulConfig
     */
    public function getConsulConfig(): ConsulConfig
    {
        return $this->consulConfig;
    }
}