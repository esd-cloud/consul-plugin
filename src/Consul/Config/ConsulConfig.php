<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/26
 * Time: 11:45
 */

namespace ESD\Plugins\Consul\Config;


use ESD\Core\Plugins\Config\BaseConfig;
use ESD\Core\Plugins\Config\ConfigException;
use ESD\Core\Server\Config\PortConfig;
use ESD\Server\Co\Server;

/**
 * Class ConsulConfig
 * @package ESD\Plugins\Consul\Config
 */
class ConsulConfig extends BaseConfig
{
    const key = "consul";
    /**
     * ip地址和端口默认为http://127.0.0.1:8500
     * @var string
     */
    protected $host = "http://127.0.0.1:8500";

    /**
     * @var ConsulServiceConfig[]|null
     */
    protected $serviceConfigs;

    /**
     * 默认注册的tags将覆盖ConsulServiceConfig中的tags配置
     * @var string[]|null
     */
    protected $defaultTags;

    /**
     * 默认查询服务的tag
     * @var string|null
     */
    protected $defaultQueryTag;

    /**
     * 查询服务的tag对照表
     * @var string[]|null
     */
    protected $serverListQueryTags;

    /**
     * 本机网卡设备
     * @var string
     */
    protected $bindNetDev = "eth0";

    /**
     * 领导名称
     * @var string|null
     */
    protected $leaderName;

    /**
     * ConsulConfig constructor.
     * @param string $host
     * @throws \ReflectionException
     */
    public function __construct(?string $host)
    {
        parent::__construct(self::key);
        if ($host != null) {
            $this->host = $host;
        }
    }

    /**
     * 获取ip
     * @param $dev
     * @return string
     */
    private function getServerIp($dev)
    {
        return exec("ip -4 addr show $dev | grep inet | awk '{print $2}' | cut -d / -f 1");
    }

    /**
     * 自动配置
     * @throws ConfigException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \ReflectionException
     */
    public function autoConfig()
    {
        $serverConfig = Server::$instance->getServerConfig();
        $normalName = $serverConfig->getName();
        $ip = $this->getServerIp($this->getBindNetDev());
        if (empty($this->getServiceConfigs())) {
            //如果没有配置ServiceConfigs那么将自动填充配置
            foreach (Server::$instance->getPortManager()->getPortConfigs() as $portConfig) {
                $agreement = "http";
                if ($portConfig->isOpenHttpProtocol()) {
                    $agreement = "http";
                    if ($portConfig->isEnableSsl()) {
                        $agreement = "https";
                    }
                } elseif ($portConfig->isOpenWebsocketProtocol()) {
                    $agreement = "ws";
                    if ($portConfig->isEnableSsl()) {
                        $agreement = "wss";
                    }
                } elseif ($portConfig->getSockType() == PortConfig::SWOOLE_SOCK_TCP || $portConfig->getSockType() == PortConfig::SWOOLE_SOCK_TCP6) {
                    $agreement = "tcp";
                }
                //设置一个服务config
                $consulServiceConfig = new ConsulServiceConfig();
                $consulServiceConfig->setName($normalName);
                $consulServiceConfig->setId($normalName . "-" . $ip . "-" . $portConfig->getPort());
                $consulServiceConfig->setAddress($ip);
                $consulServiceConfig->setPort($portConfig->getPort());
                $consulServiceConfig->setMeta(["server" => "esd", "agreement" => $agreement]);
                $consulCheckConfig = new ConsulCheckConfig();
                $consulCheckConfig->setInterval("10s");
                $consulCheckConfig->setTlsSkipVerify(true);
                $consulCheckConfig->setNotes("esd auto check");
                $consulCheckConfig->setStatus("passing");
                $consulServiceConfig->setCheckConfig($consulCheckConfig);
                if ($portConfig->isOpenHttpProtocol() || $portConfig->isOpenWebsocketProtocol()) {
                    $consulCheckConfig->setHttp("$agreement://$ip:{$portConfig->getPort()}/actuator/health");
                    $this->addServiceConfig($consulServiceConfig);
                } elseif ($agreement == "tcp") {
                    $consulCheckConfig = new ConsulCheckConfig();
                    $consulCheckConfig->setTcp("$agreement://$ip:{$portConfig->getPort()}");
                    $this->addServiceConfig($consulServiceConfig);
                }
            }
        }
        //修改全局的配置
        foreach ($this->getServiceConfigs() as $consulServiceConfig) {
            if (empty($consulServiceConfig->getName())) {
                throw new ConfigException("ConsulServiceConfig缺少name字段");
            }
            if (!empty($this->getDefaultTags())) {
                $consulServiceConfig->setTags($this->getDefaultTags());
            }
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
        $this->serviceConfigs[$consulServiceConfig->getName()] = $consulServiceConfig;
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
        if (empty($serviceConfigs)) {
            $this->serviceConfigs = $serviceConfigs;
        } else {
            foreach ($serviceConfigs as $key => $value) {
                if (is_array($value)) {
                    $this->serviceConfigs[$key] = new ConsulServiceConfig();
                    $this->serviceConfigs[$key]->setName($key);
                    $this->serviceConfigs[$key]->buildFromConfig($value);
                } else if ($value instanceof ConsulServiceConfig) {
                    $this->serviceConfigs[$key] = $value;
                }
            }
        }
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