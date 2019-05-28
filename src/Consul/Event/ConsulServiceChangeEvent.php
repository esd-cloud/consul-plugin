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

class ConsulServiceChangeEvent extends Event
{
    const ConsulServiceChangeEvent = "ConsulServiceChangeEvent";

    public function __construct(ConsulServiceListInfo $consulServiceListInfo)
    {
        parent::__construct(self::ConsulServiceChangeEvent, $consulServiceListInfo);
    }

    /**
     * @return ConsulServiceListInfo
     */
    public function getConsulServiceListInfo(): ConsulServiceListInfo
    {
        return $this->getData();
    }
}