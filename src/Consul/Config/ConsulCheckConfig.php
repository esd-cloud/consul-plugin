<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/26
 * Time: 11:40
 */

namespace GoSwoole\Plugins\Consul\Config;


/**
 * Class ConsulCheckConfig
 * @package GoSwoole\Plugins\Consul\Config
 */
class ConsulCheckConfig
{
    /**
     * 指定运行此检查的频率。这是HTTP和TCP检查所必需的。
     * @var string|null
     */
    private $interval;

    /**
     * 指定人类的任意信息
     * @var string|null
     */
    private $notes;

    /**
     * 指定在此时间之后与服务关联的检查应取消注册。
     * 这被指定为带有后缀的持续时间，如“10m”。
     * 如果检查处于严重状态且超过此配置值，则其关联服务（及其所有相关检查）将自动取消注册。
     * 最小超时为1分钟，获取关键服务的进程每30秒运行一次，因此可能需要稍长于配置的超时才能触发取消注册。
     * 通常应配置超时，该超时比给定服务的任何预期可恢复中断要长得多。
     * @var string|null
     */
    private $deregisterCriticalServiceAfter;

    /**
     * 指定gRPC支持标准gRPC运行状况检查协议的检查端点 。
     * Interval通过探测配置的端点，将在给定的状态下更新检查状态。
     * @var string|null
     */
    private $gRPC;

    /**
     * 指定是否使用TLS进行此gRPC运行状况检查。
     * 如果启用了TLS，则默认情况下，需要有效的TLS证书。可以通过设置TLSSkipVerify为关闭证书验证true。
     * @var bool|null
     */
    private $gRPCUseTLS;

    /**
     * 指定HTTP检查以GET对每个值HTTP（预期为URL）执行请求Interval。
     * 如果响应是任何2xx代码，则检查是passing。如果是响应429 Too Many Requests，则检查是warning。
     * 否则，检查是 critical。HTTP检查还支持SSL。默认情况下，需要有效的SSL证书。
     * 可以使用TLSSkipVerify。来控制证书验证 。
     * @var string|null
     */
    private $http;

    /**
     * 指定用于HTTP检查的其他HTTP方法。
     * 如果未指定任何值，GET则使用。
     * @var string|null
     */
    private $method;

    /**
     * 指定应为HTTP检查设置的一组标头。
     * 每个标头可以有多个值。
     * @var string[]|null
     */
    private $header;

    /**
     * 在脚本，HTTP，TCP或gRPC检查的情况下指定传出连接的超时。
     * 可以以“10s”或“5m”的形式指定（即分别为10秒或5分钟）。
     * @var string|null
     */
    private $timeout;

    /**
     * 指定是否不应验证HTTPS检查的证书。
     * @var bool|null
     */
    private $tlsSkipVerify;

    /**
     * 指定a TCP连接每个的值TCP （预期是IP或主机名加端口组合）Interval。
     * 如果连接尝试成功，则检查为passing。如果连接尝试失败，则检查为critical。
     * 如果主机名解析为IPv4和IPv6地址，则将尝试对这两个地址进行尝试，并且第一次成功的连接尝试将导致成功检查。
     * @var string|null
     */
    private $tcp;

    /**
     * 指定这是TTL检查，并且必须定期使用TTL端点来更新检查的状态。
     * @var string|null
     */
    private $ttl;

    /**
     * 指定运行状况检查的初始状态。
     * @var string|null
     */
    private $status;

    /**
     * @return string|null
     */
    public function getInterval(): ?string
    {
        return $this->interval;
    }

    /**
     * @param string|null $interval
     */
    public function setInterval(?string $interval): void
    {
        $this->interval = $interval;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string|null
     */
    public function getTtl(): ?string
    {
        return $this->ttl;
    }

    /**
     * @param string|null $ttl
     */
    public function setTtl(?string $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * @return string|null
     */
    public function getTcp(): ?string
    {
        return $this->tcp;
    }

    /**
     * @param string|null $tcp
     */
    public function setTcp(?string $tcp): void
    {
        $this->tcp = $tcp;
    }

    /**
     * @return bool|null
     */
    public function getTlsSkipVerify(): ?bool
    {
        return $this->tlsSkipVerify;
    }

    /**
     * @param bool|null $tlsSkipVerify
     */
    public function setTlsSkipVerify(?bool $tlsSkipVerify): void
    {
        $this->tlsSkipVerify = $tlsSkipVerify;
    }

    /**
     * @return string|null
     */
    public function getTimeout(): ?string
    {
        return $this->timeout;
    }

    /**
     * @param string|null $timeout
     */
    public function setTimeout(?string $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * @return string[]|null
     */
    public function getHeader(): ?array
    {
        return $this->header;
    }

    /**
     * @param string[]|null $header
     */
    public function setHeader(?array $header): void
    {
        $this->header = $header;
    }

    /**
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * @param string|null $method
     */
    public function setMethod(?string $method): void
    {
        $this->method = $method;
    }

    /**
     * @return string|null
     */
    public function getHttp(): ?string
    {
        return $this->http;
    }

    /**
     * @param string|null $http
     */
    public function setHttp(?string $http): void
    {
        $this->http = $http;
    }

    /**
     * @return bool|null
     */
    public function getGRPCUseTLS(): ?bool
    {
        return $this->gRPCUseTLS;
    }

    /**
     * @param bool|null $gRPCUseTLS
     */
    public function setGRPCUseTLS(?bool $gRPCUseTLS): void
    {
        $this->gRPCUseTLS = $gRPCUseTLS;
    }

    /**
     * @return string|null
     */
    public function getGRPC(): ?string
    {
        return $this->gRPC;
    }

    /**
     * @param string|null $gRPC
     */
    public function setGRPC(?string $gRPC): void
    {
        $this->gRPC = $gRPC;
    }

    /**
     * @return string|null
     */
    public function getDeregisterCriticalServiceAfter(): ?string
    {
        return $this->deregisterCriticalServiceAfter;
    }

    /**
     * @param string|null $deregisterCriticalServiceAfter
     */
    public function setDeregisterCriticalServiceAfter(?string $deregisterCriticalServiceAfter): void
    {
        $this->deregisterCriticalServiceAfter = $deregisterCriticalServiceAfter;
    }

    /**
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @param string|null $notes
     */
    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * 配置
     */
    public function buildConfig()
    {
        return array_filter([
            "Interval" =>$this->getInterval(),
            "Notes"=>$this->getNotes(),
            "DeregisterCriticalServiceAfter"=>$this->getDeregisterCriticalServiceAfter(),
            "GRPC"=>$this->getGRPC(),
            "GRPCUseTLS"=>$this->getGRPCUseTLS(),
            "HTTP"=>$this->getHttp(),
            "Method"=>$this->getMethod(),
            "Header"=>$this->getHeader(),
            "Timeout"=>$this->getTimeout(),
            "TLSSkipVerify"=>$this->getTlsSkipVerify(),
            "TCP"=>$this->getTcp(),
            "TTL"=>$this->getTtl(),
            "Status"=>$this->getStatus()
        ]);
    }

}