<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/26
 * Time: 11:45
 */

namespace GoSwoole\Plugins\Consul\Config;

/**
 * Class ConsulConfig
 * @package GoSwoole\Plugins\Consul\Config
 */
class ConsulConfig
{
    /**
     * ip地址和端口默认为http://127.0.0.1:8500
     * @var string
     */
    private $host = "http://127.0.0.1:8500";

    /**
     * @var ConsulServiceConfig[]|null
     */
    private $serviceConfigs;

    /**
     * 默认注册的tags将覆盖ConsulServiceConfig中的tags配置
     * @var string[]|null
     */
    private $defaultTags;

    /**
     * 默认查询服务的tag
     * @var string|null
     */
    private $defaultQueryTag;

    /**
     * 查询服务的tag对照表
     * @var string[]|null
     */
    private $serverListQueryTags;

    /**
     * 本机网卡设备
     * @var string
     */
    private $bindNetDev = "eth0";

    /**
     * 领导名称
     * @var string|null
     */
    private $leaderName;

    /**
     * ConsulConfig constructor.
     * @param string $host
     */
    public function __construct(?string $host)
    {
        if ($host != null) {
            $this->host = $host;
        }
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function addServiceConfig(ConsulServiceConfig $consulServiceConfig)
    {
        if ($this->serviceConfigs == null) $this->serviceConfigs = [];
        $this->serviceConfigs[] = $consulServiceConfig;
    }

    /**
     * @return ConsulServiceConfig[]|null
     */
    public function getServiceConfigs(): ?array
    {
        return $this->serviceConfigs;
    }

    /**
     * @param ConsulServiceConfig[]|null $serviceConfigs
     */
    public function setServiceConfigs(?array $serviceConfigs): void
    {
        $this->serviceConfigs = $serviceConfigs;
    }

    /**
     * @return string[]|null
     */
    public function getDefaultTags(): ?array
    {
        return $this->defaultTags;
    }

    /**
     * @param string[]|null $defaultTags
     */
    public function setDefaultTags(?array $defaultTags): void
    {
        $this->defaultTags = $defaultTags;
    }

    /**
     * @return string|null
     */
    public function getDefaultQueryTag(): ?string
    {
        return $this->defaultQueryTag;
    }

    /**
     * @param string|null $defaultQueryTag
     */
    public function setDefaultQueryTag(?string $defaultQueryTag): void
    {
        $this->defaultQueryTag = $defaultQueryTag;
    }

    /**
     * @return string[]|null
     */
    public function getServerListQueryTags(): ?array
    {
        return $this->serverListQueryTags;
    }

    /**
     * @param string[]|null $serverListQueryTags
     */
    public function setServerListQueryTags(?array $serverListQueryTags): void
    {
        $this->serverListQueryTags = $serverListQueryTags;
    }

    /**
     * @return string
     */
    public function getBindNetDev(): string
    {
        return $this->bindNetDev;
    }

    /**
     * @param string $bindNetDev
     */
    public function setBindNetDev(string $bindNetDev): void
    {
        $this->bindNetDev = $bindNetDev;
    }

    /**
     * @return string|null
     */
    public function getLeaderName(): ?string
    {
        return $this->leaderName;
    }

    /**
     * @param string|null $leaderName
     */
    public function setLeaderName(?string $leaderName): void
    {
        $this->leaderName = $leaderName;
    }

}