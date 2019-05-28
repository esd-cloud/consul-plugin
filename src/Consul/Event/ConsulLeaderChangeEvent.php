<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/26
 * Time: 15:42
 */

namespace ESD\Plugins\Consul\Event;


use ESD\Core\Plugins\Event\Event;

class ConsulLeaderChangeEvent extends Event
{
    const ConsulLeaderChangeEvent = "ConsulLeaderChangeEvent";

    public function __construct(bool $isLeader)
    {
        parent::__construct(self::ConsulLeaderChangeEvent, $isLeader);
    }

    public function isLeader(): bool
    {
        return $this->getData();
    }
}