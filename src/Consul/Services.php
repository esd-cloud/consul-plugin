<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/26
 * Time: 17:00
 */

namespace GoSwoole\Plugins\Consul;


use GoSwoole\BaseServer\Server\Server;
use GoSwoole\Plugins\Consul\Beans\ConsulServiceInfo;
use GoSwoole\Plugins\Consul\Event\ConsulAddServiceMonitorEvent;
use GoSwoole\Plugins\Consul\Event\ConsulServiceChangeEvent;

/**
 * 通过这个类获取服务
 * Class Services
 * @package GoSwoole\Plugins\Consul
 */
class Services
{
    /**
     * @var ConsulServiceInfo[]
     */
    private static $services = [];

    /**
     * 服务变更
     * @param ConsulServiceChangeEvent $consulServiceChangeEvent
     */
    public static function modifyServices(ConsulServiceChangeEvent $consulServiceChangeEvent)
    {
        self::$services[$consulServiceChangeEvent->getConsulServiceListInfo()->getServiceName()]
            = $consulServiceChangeEvent->getConsulServiceListInfo()->getConsulServiceInfos();
        //同时本进程触发更为细化的携带服务名的ConsulServiceChangeEvent事件
        $consulServiceChangeEvent->setType(
            ConsulServiceChangeEvent::ConsulServiceChangeEvent . "::" . $consulServiceChangeEvent->getConsulServiceListInfo()->getServiceName());
        Server::$instance->getEventDispatcher()->dispatchEvent($consulServiceChangeEvent);
    }

    /**
     * 获取服务
     * @param string $service
     * @return ConsulServiceInfo[]
     */
    public static function getServices(string $service): array
    {
        $consulServiceInfos = self::$services[$service] ?? null;
        //为null只有二种情况，一是第一次获取，二是reload后进程数据丢失，这时重新获取
        if ($consulServiceInfos == null) {
            Server::$instance->getEventDispatcher()->dispatchProcessEvent(
                new ConsulAddServiceMonitorEvent($service),
                Server::$instance->getProcessManager()->getProcessFromName(ConsulPlugin::processName)
            );
            $channel = Server::$instance->getEventDispatcher()->listen(ConsulServiceChangeEvent::ConsulServiceChangeEvent . "::" . $service, null, true);
            $consulGetServiceEvent = $channel->pop();
            if ($consulGetServiceEvent instanceof ConsulServiceChangeEvent) {
                $consulServiceInfos = $consulGetServiceEvent->getConsulServiceListInfo()->getConsulServiceInfos();
            }
        }
        $consulPlugin = Server::$instance->getPlugManager()->getPlug(ConsulPlugin::class);
        if ($consulPlugin instanceof ConsulPlugin) {
            $consulConfig = $consulPlugin->getConsulConfig();
            $serverListQueryTags = $consulConfig->getServerListQueryTags();
            $tag = null;
            if ($serverListQueryTags != null) {
                $tag = $serverListQueryTags[$service] ?? $consulConfig->getDefaultQueryTag();
            }
            if ($tag != null) {
                foreach ($consulServiceInfos as $key => $value) {
                    if (empty($value->getServiceTags())) {
                        unset($consulServiceInfos[$key]);
                    } else {
                        if (!in_array($tag, $value->getServiceTags())) {
                            unset($consulServiceInfos[$key]);
                        }
                    }
                }
            }
        }
        return $consulServiceInfos;
    }
}