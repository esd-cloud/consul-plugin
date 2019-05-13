<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/24
 * Time: 14:42
 */

namespace ESD\Plugins\Consul;

use ESD\BaseServer\Exception;
use ESD\BaseServer\Plugins\Event\ProcessEvent;
use ESD\BaseServer\Plugins\Logger\GetLogger;
use ESD\BaseServer\Server\Context;
use ESD\BaseServer\Server\Exception\ConfigException;
use ESD\BaseServer\Server\Plugin\AbstractPlugin;
use ESD\BaseServer\Server\Plugin\PluginInterfaceManager;
use ESD\BaseServer\Server\Server;
use ESD\Plugins\Actuator\ActuatorPlugin;
use ESD\Plugins\Consul\Config\ConsulConfig;
use ESD\Plugins\Consul\Event\ConsulLeaderChangeEvent;
use ESD\Plugins\Consul\Event\ConsulServiceChangeEvent;


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
     * @throws Exception
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
     * @return mixed
     * @throws ConfigException
     * @throws \ReflectionException
     */
    public function beforeServerStart(Context $context)
    {
        //添加一个helper进程
        $context->getServer()->addProcess(self::processName, HelperConsulProcess::class, self::processGroupName);
        //自动配置
        $this->consulConfig->autoConfig();
        $this->consulConfig->merge();
    }

    /**
     * 在进程启动前
     * @param Context $context
     * @return mixed
     */
    public function beforeProcessStart(Context $context)
    {
        //每个进程监听Leader变更
        goWithContext(function () {
            $channel = Server::$instance->getEventDispatcher()->listen(ConsulLeaderChangeEvent::ConsulLeaderChangeEvent);
            while (true) {
                $event = $channel->pop();
                if ($event instanceof ConsulLeaderChangeEvent) {
                    $leaderStatus = $event->isLeader() ? "true" : "false";
                    $this->debug("收到Leader变更事件：$leaderStatus");
                    Leader::$isLeader = $event->isLeader();
                }
            }
        });

        //每个进程监听Service变更
        goWithContext(function () {
            $channel = Server::$instance->getEventDispatcher()->listen(ConsulServiceChangeEvent::ConsulServiceChangeEvent);
            while (true) {
                $event = $channel->pop();
                if ($event instanceof ConsulServiceChangeEvent) {
                    $this->debug("收到Service变更事件：{$event->getConsulServiceListInfo()->getServiceName()}");
                    Services::modifyServices($event);
                }
            }
        });

        //Helper进程
        if ($context->getServer()->getProcessManager()->getCurrentProcess()->getProcessName() === self::processName) {
            goWithContext(function () {
                $this->consul = new Consul($this->consulConfig);
                //进程监听关服信息
                $channel = Server::$instance->getEventDispatcher()->listen(ProcessEvent::ProcessStopEvent, null, true);
                $channel->pop();
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