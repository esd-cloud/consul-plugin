<?php
/**
 * Created by PhpStorm.
 * User: administrato
 * Date: 2019/4/26
 * Time: 17:00
 */

namespace GoSwoole\Plugins\Consul;


use GoSwoole\Plugins\Consul\Beans\ConsulServiceInfo;
use GoSwoole\Plugins\Consul\Beans\ConsulServiceListInfo;

class Services
{
    /**
     * @var ConsulServiceInfo[]
     */
    private static $services = [];

    /**
     * @param ConsulServiceListInfo $consulServiceListInfo
     */
    public static function modifyServices(ConsulServiceListInfo $consulServiceListInfo)
    {
        self::$services[$consulServiceListInfo->getServiceName()] = $consulServiceListInfo->getConsulServiceInfos();
    }

    public static function getServices(string $service)
    {
        return self::$services[$service] ?? null;
    }
}