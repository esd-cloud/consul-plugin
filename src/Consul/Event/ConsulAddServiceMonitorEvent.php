<?php
/**
 * Created by PhpStorm.
 * User: administrato
 * Date: 2019/4/26
 * Time: 15:42
 */

namespace GoSwoole\Plugins\Consul\Event;


use GoSwoole\BaseServer\Plugins\Event\Event;

class ConsulAddServiceMonitorEvent extends Event
{
    const ConsulAddServiceMonitorEvent = "ConsulAddServiceMonitorEvent";

    public function __construct(string $service)
    {
        parent::__construct(self::ConsulAddServiceMonitorEvent, $service);
    }

    /**
     * @return string
     */
    public function getService(): string
    {
        return $this->getData();
    }
}