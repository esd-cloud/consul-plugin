<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/26
 * Time: 15:42
 */

namespace ESD\Plugins\Consul\Event;


use ESD\Core\Plugins\Event\Event;
use ESD\Plugins\Consul\Beans\ConsulServiceListInfo;

class ConsulOneServiceChangeEvent extends Event
{
    const ConsulOneServiceChangeEvent = "ConsulOneServiceChangeEvent";

    public function __construct(string $type, ConsulServiceListInfo $consulServiceListInfo)
    {
        parent::__construct(self::ConsulOneServiceChangeEvent . "::$type", $consulServiceListInfo);
    }

    /**
     * @return ConsulServiceListInfo
     */
    public function getConsulServiceListInfo(): ConsulServiceListInfo
    {
        return $this->getData();
    }
}