<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/26
 * Time: 10:29
 */

namespace GoSwoole\Plugins\Consul\Config;

use GoSwoole\BaseServer\Plugins\Config\BaseConfig;

/**
 * ConsulService配置
 * Class ConsulServiceConfig
 * @package GoSwoole\Plugins\Consul\Config
 */
class ConsulServiceConfig extends BaseConfig
{
    const key = "consul.service_configs";
    /**
     * 服务名字,默认会是server名字
     * @var string|null
     */
    protected $name;

    /**
     * 指定此服务的唯一ID。每个代理必须是唯一的。Name如果未提供，则默认为参数。
     * @var string|null
     */
    protected $id;

    /**
     * 指定要分配给服务的标记列表。
     * 这些标记可用于以后的过滤，并通过API公开。
     * @var string[]|null
     */
    protected $tags;

    /**
     * 指定服务的地址。如果未提供，则在DNS查询期间将代理的地址用作服务的地址。
     * @var string|null
     */
    protected $address;

    /**
     * 指定服务的端口。
     * @var int|null
     */
    protected $port;

    /**
     * 指定链接到服务实例的任意KV元数据。
     * @var string[]|null
     */
    protected $meta;

    /**
     * @var ConsulCheckConfig|null
     */
    protected $checkConfig;

    public function __construct()
    {
        parent::__construct(self::key);
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string[]|null
     */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    /**
     * @param string[]|null $tags
     */
    public function setTags(?array $tags): void
    {
        $this->tags = $tags;
    }

    /**
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @param string|null $address
     */
    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    /**
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @param int|null $port
     */
    public function setPort(?int $port): void
    {
        $this->port = $port;
    }

    /**
     * @return string[]|null
     */
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /**
     * @param string[]|null $meta
     */
    public function setMeta(?array $meta): void
    {
        $this->meta = $meta;
    }


    /**
     * @return ConsulCheckConfig|null
     */
    public function getCheckConfig(): ?ConsulCheckConfig
    {
        return $this->checkConfig;
    }

    /**
     * @param ConsulCheckConfig|null $checkConfig
     * @throws \ReflectionException
     */
    public function setCheckConfig($checkConfig): void
    {
        if (is_array($checkConfig)){
            $this->checkConfig = new ConsulCheckConfig();
            $this->checkConfig->buildFromConfig($checkConfig);
        }elseif($checkConfig instanceof ConsulCheckConfig){
            $this->checkConfig = $checkConfig;
        }
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * 生成配置文件
     */
    public function buildConfig(): array
    {
        return array_filter([
            "Name" => $this->getName(),
            "ID" => $this->getId(),
            "Tags" => $this->getTags(),
            "Address" => $this->getAddress(),
            "Meta" => $this->getMeta(),
            "Port" => $this->getPort(),
            "Check" => $this->buildCheckConfigs()
        ]);
    }

    /**
     * 生成check的配置
     */
    protected function buildCheckConfigs()
    {
        if (empty($this->checkConfig)) return null;
        return $this->checkConfig->buildConfig();
    }

}