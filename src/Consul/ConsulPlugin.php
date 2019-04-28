<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/24
 * Time: 14:42
 */

namespace GoSwoole\Plugins\Consul;

use GoSwoole\BaseServer\Plugins\Event\ProcessEvent;
use GoSwoole\BaseServer\Plugins\Logger\GetLogger;
use GoSwoole\BaseServer\Server\Context;
use GoSwoole\BaseServer\Server\Plugin\AbstractPlugin;
use GoSwoole\BaseServer\Server\Plugin\PluginInterfaceManager;
use GoSwoole\BaseServer\Server\Server;
use GoSwoole\Plugins\Actuator\ActuatorPlugin;
use GoSwoole\Plugins\Consul\Config\ConsulConfig;
use GoSwoole\Plugins\Consul\Event\ConsulLeaderChangeEvent;
use GoSwoole\Plugins\Consul\Event\ConsulServiceChangeEvent;


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
     * @throws \GoSwoole\BaseServer\Exception
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
     * @throws \GoSwoole\BaseServer\Server\Exception\ConfigException
     */
    public function beforeServerStart(Context $context)
    {
        //添加一个helper进程
        $context->getServer()->addProcess(self::processName, HelperConsulProcess::class, self::processGroupName);
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
                    Services::modifyServices($event->getConsulServiceListInfo());
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
            });
        }
        $this->ready();
    }
}