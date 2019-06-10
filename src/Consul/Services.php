<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/26
 * Time: 17:00
 */

namespace ESD\Plugins\Consul;

use ESD\Plugins\Consul\Beans\ConsulServiceInfo;
use ESD\Plugins\Consul\Config\ConsulConfig;
use ESD\Plugins\Consul\Event\ConsulAddServiceMonitorEvent;
use ESD\Plugins\Consul\Event\ConsulOneServiceChangeEvent;
use ESD\Plugins\Consul\Event\ConsulServiceChangeEvent;
use ESD\Server\Co\Server;

/**
 * 通过这个类获取服务
 * Class Services
 * @package ESD\Plugins\Consul
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
        $consulOneServiceChangeEvent = new ConsulOneServiceChangeEvent($consulServiceChangeEvent->getConsulServiceListInfo()->getServiceName(),
            $consulServiceChangeEvent->getConsulServiceListInfo()
        );
        Server::$instance->getEventDispatcher()->dispatchEvent($consulOneServiceChangeEvent);
    }

    /**
     * 获取服务
     * @param string $service
     * @return ConsulServiceInfo[]
     */
    public static function getServices(string $service): ?array
    {
        $consulServiceInfos = self::$services[$service] ?? null;
        //为null只有二种情况，一是第一次获取，二是reload后进程数据丢失，这时重新获取
        if ($consulServiceInfos == null) {
            Server::$instance->getEventDispatcher()->dispatchProcessEvent(
                new ConsulAddServiceMonitorEvent($service),
                Server::$instance->getProcessManager()->getProcessFromName(ConsulPlugin::processName)
            );
            $call = Server::$instance->getEventDispatcher()->listen(ConsulOneServiceChangeEvent::ConsulOneServiceChangeEvent . "::" . $service, null, true);
            /** @var ConsulOneServiceChangeEvent $consulGetServiceEvent */
            $consulGetServiceEvent = $call->wait();
            $consulServiceInfos = $consulGetServiceEvent->getConsulServiceListInfo()->getConsulServiceInfos();
        }
        /** @var ConsulConfig $consulConfig */
        $consulConfig = DIGet(ConsulConfig::class);
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

        return $consulServiceInfos;
    }
}