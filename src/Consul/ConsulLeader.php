<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/26
 * Time: 16:21
 */

namespace ESD\Plugins\Consul;

use ESD\Psr\Cloud\Leader;

/**
 * 通过这个类判断是否是leader
 * Class ConsulLeader
 * @package ESD\Plugins\Consul
 */
class ConsulLeader implements Leader
{
    /**
     * @var bool
     */
    public $leader;

    /**
     * @return bool
     */
    public function isLeader(): bool
    {
        return $this->leader;
    }

    /**
     * @param bool $leader
     */
    public function setLeader(bool $leader): void
    {
        $this->leader = $leader;
    }

}