<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/26
 * Time: 10:46
 */

namespace GoSwoole\Plugins\Consul;


use GoSwoole\BaseServer\Plugins\Logger\GetLogger;
use GoSwoole\BaseServer\Server\Exception\ConfigException;
use GoSwoole\BaseServer\Server\Server;
use GoSwoole\Consul\Health;
use GoSwoole\Consul\KV;
use GoSwoole\Consul\ServiceFactory;
use GoSwoole\Consul\Session;
use GoSwoole\Plugins\Consul\Beans\ConsulServiceInfo;
use GoSwoole\Plugins\Consul\Beans\ConsulServiceListInfo;
use GoSwoole\Plugins\Consul\Config\ConsulCheckConfig;
use GoSwoole\Plugins\Consul\Config\ConsulConfig;
use GoSwoole\Plugins\Consul\Config\ConsulServiceConfig;
use GoSwoole\Plugins\Consul\Event\ConsulAddServiceMonitorEvent;
use GoSwoole\Plugins\Consul\Event\ConsulLeaderChangeEvent;
use GoSwoole\Plugins\Consul\Event\ConsulServiceChangeEvent;
use SensioLabs\Consul\ConsulResponse;
use SensioLabs\Consul\Services\Agent;
use SensioLabs\Consul\Services\AgentInterface;
use SensioLabs\Consul\Services\HealthInterface;
use SensioLabs\Consul\Services\KVInterface;
use SensioLabs\Consul\Services\SessionInterface;

/**
 * Class Consul
 * @package GoSwoole\Plugins\Consul
 */
class Consul
{
    use GetLogger;
    /**
     * @var bool
     */
    private $isLeader = false;
    /**
     * @var ConsulConfig
     */
    private $consulConfig;

    /**
     * @var string[]
     */
    private $listonServices = [];

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var ServiceFactory
     */
    private $sf;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var KV
     */
    private $kv;
    /**
     * @var Agent
     */
    private $agent;
    /**
     * @var Health
     */
    private $health;
    /**
     * 同步
     * @var \SensioLabs\Consul\ServiceFactory
     */
    private $syncSf;
    /**
     * 同步Session
     * @var SessionInterface
     */
    private $syncSession;
    /**
     * 同步Agent
     * @var AgentInterface
     */
    private $syncAgent;

    /**
     * Consul constructor.
     * @param ConsulConfig $consulConfig
     * @throws ConfigException
     */
    public function __construct(ConsulConfig $consulConfig)
    {
        $this->consulConfig = $consulConfig;
        $serverConfig = Server::$instance->getServerConfig();
        $normalName = $serverConfig->getName();
        $ip = $this->getServerIp($this->consulConfig->getBindNetDev());
        if (empty($this->consulConfig->getServiceConfigs())) {
            //如果没有配置ServiceConfigs那么将自动填充配置
            foreach (Server::$instance->getPortManager()->getPorts() as $port) {
                $agreement = "http";
                if ($port->isHttp()) {
                    $agreement = "http";
                    if ($port->getPortConfig()->isEnableSsl()) {
                        $agreement = "https";
                    }
                } elseif ($port->isWebSocket()) {
                    $agreement = "ws";
                    if ($port->getPortConfig()->isEnableSsl()) {
                        $agreement = "wss";
                    }
                } elseif ($port->isTcp()) {
                    $agreement = "tcp";
                }
                $consulServiceConfig = new ConsulServiceConfig();
                $consulServiceConfig->setName("$normalName");
                $consulServiceConfig->setId($normalName . "-" . $ip . "-" . $port->getPortConfig()->getPort());
                $consulServiceConfig->setAddress($ip);
                $consulServiceConfig->setPort($port->getPortConfig()->getPort());
                $consulServiceConfig->setMeta(["server" => "go-swoole", "agreement" => $agreement]);
                $consulCheckConfig = new ConsulCheckConfig();
                $consulCheckConfig->setInterval("10s");
                $consulCheckConfig->setTlsSkipVerify(true);
                $consulCheckConfig->setNotes("go-swoole auto check");
                $consulCheckConfig->setStatus("passing");
                $consulServiceConfig->setCheckConfig($consulCheckConfig);
                if ($port->isHttp() || $port->isWebSocket()) {
                    $consulCheckConfig->setHttp("$agreement://$ip:{$port->getPortConfig()->getPort()}/actuator/health");
                    $this->consulConfig->addServiceConfig($consulServiceConfig);
                } elseif ($port->isTcp()) {
                    $consulCheckConfig = new ConsulCheckConfig();
                    $consulCheckConfig->setTcp("$agreement://$ip:{$port->getPortConfig()->getPort()}");
                    $this->consulConfig->addServiceConfig($consulServiceConfig);
                }
            }
        }
        //修改全局的配置
        foreach ($this->consulConfig->getServiceConfigs() as $consulServiceConfig) {
            if (empty($consulServiceConfig->getName())) {
                throw new ConfigException("ConsulServiceConfig缺少name字段");
            }
            if (!empty($consulConfig->getDefaultTags())) {
                $consulServiceConfig->setTags($consulConfig->getDefaultTags());
            }
        }
        //生成配置文件并注册
        $this->sf = new ServiceFactory(["base_uri" => $consulConfig->getHost(), "http_errors" => false], Server::$instance->getLog());
        $this->session = $this->sf->get(SessionInterface::class);
        $this->kv = $this->sf->get(KVInterface::class);
        $this->agent = $this->sf->get(AgentInterface::class);
        $this->health = $this->sf->get(HealthInterface::class);

        $this->syncSf = new \SensioLabs\Consul\ServiceFactory(["base_uri" => $this->consulConfig->getHost(), "http_errors" => false], Server::$instance->getLog());
        $this->syncSession = $this->syncSf->get(SessionInterface::class);
        $this->syncAgent = $this->sf->get(AgentInterface::class);

        foreach ($this->consulConfig->getServiceConfigs() as $consulServiceConfig) {
            $body = $consulServiceConfig->buildConfig();
            $this->agent->registerService($body);
        }
        //监听需要监控的服务的事件
        goWithContext(function () {
            $eventChannel = Server::$instance->getEventDispatcher()->listen(ConsulAddServiceMonitorEvent::ConsulAddServiceMonitorEvent);
            while (true) {
                $consulAddServiceMonitorEvent = $eventChannel->pop();
                if ($consulAddServiceMonitorEvent instanceof ConsulAddServiceMonitorEvent) {
                    $service = $consulAddServiceMonitorEvent->getService();
                    if (!array_key_exists($service, $this->listonServices)) {
                        goWithContext(function () use ($service) {
                            $this->monitorService($service, 0);
                        });
                    }
                }
            }
        });
        //Leader监听
        if (!empty($consulConfig->getLeaderName())) {
            goWithContext(function () {
                //先尝试获取下leader
                $this->getLeader();
            });
        } else {
            $this->setIsLeader(true);
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
     * 添加监听
     * @param string $service
     * @param int $index
     */
    private function monitorService(string $service, int $index)
    {
        try {
            $response = $this->health->service($service, ["passing" => true, "index" => $index, "wait" => "1m"], 120);
        } catch (\Throwable $e) {
            //出错会一直重试
            $this->error($e);
            $this->monitorService($service, $index);
            return;
        }
        if ($response instanceof ConsulResponse) {
            $index = $response->getHeaders()["x-consul-index"][0];
            $consulServiceInfos = [];
            foreach ($response->json() as $one) {
                $oneService = $one['Service'];
                $consulServiceInfo = new ConsulServiceInfo($oneService['Service'], $oneService['ID'], $oneService['Address'], $oneService['Port'], $oneService['Meta']);
                $consulServiceInfos[] = $consulServiceInfo;
            }
            //广播
            Server::$instance->getEventDispatcher()->dispatchProcessEvent(
                new ConsulServiceChangeEvent(new ConsulServiceListInfo($service, $consulServiceInfos)),
                ... Server::$instance->getProcessManager()->getProcesses()
            );
            $this->monitorService($service, $index);
        }
    }

    /**
     * 竞选leader
     */
    private function getLeader()
    {
        if ($this->sessionId != null) {
            $this->session->destroy($this->sessionId);
        }
        try {
            //获取SessionId
            $this->sessionId = $this->session->create(
                [
                    'LockDelay' => 0,
                    'Behavior' => 'release',
                    'Name' => $this->consulConfig->getLeaderName()
                ])->json()['ID'];
            $lockAcquired = $this->kv->put("{$this->consulConfig->getLeaderName()}/leader", 'a value', ['acquire' => $this->sessionId])->json();
            if (false === $lockAcquired) {
                $this->setIsLeader(false);
                //监控Leader
                $this->debug("没有获取到Leader");
            } else {
                //获取到了
                $this->debug("获取到Leader");
                $this->setIsLeader(true);
            }
            //监控Leader
            $this->monitorLeader(0);
        } catch (\Throwable $e) {
            $this->error($e);
            $this->getLeader();
        }
    }

    /**
     * 监控Leader变化
     * @param int $index
     */
    private function monitorLeader(int $index)
    {
        try {
            $response = $this->kv->get("{$this->consulConfig->getLeaderName()}/leader", ["index" => $index, "wait" => "1m"], 120);
        } catch (\Throwable $e) {
            //出错会一直重试
            $this->error($e);
            $this->monitorLeader($index);
            return;
        }
        if ($response instanceof ConsulResponse) {
            $index = $response->getHeaders()["x-consul-index"][0];
            $session = $response->json()[0]['Session'] ?? null;
            if ($session == null)//代表没有Leader
            {
                $this->debug("目前集群没有Leader");
                $this->getLeader();
            } else {
                if ($session != $this->sessionId) {
                    $this->debug("目前集群存在Leader，监控Leader变化");
                    $this->setIsLeader(false);
                }
                $this->monitorLeader($index);
            }
        }
    }

    /**
     * @return bool
     */
    public function isLeader(): bool
    {
        return $this->isLeader;
    }

    /**
     * @param bool $isLeader
     */
    public function setIsLeader(bool $isLeader): void
    {
        if ($this->isLeader != $isLeader) {
            $this->isLeader = $isLeader;
            //广播
            Server::$instance->getEventDispatcher()->dispatchProcessEvent(
                new ConsulLeaderChangeEvent($isLeader),
                ... Server::$instance->getProcessManager()->getProcesses()
            );
        }
    }

    /**
     * 释放Leader
     * @param bool $useAsync
     */
    public function releaseLeader($useAsync = true)
    {
        if (!empty($this->sessionId)) {
            $this->debug("释放session：$this->sessionId");
            if ($useAsync) {
                //异步
                $this->session->destroy($this->sessionId);
            } else {
                //注意这里需要用同步请求，因为关服无法使用协程方案
                $this->syncSession->destroy($this->sessionId);
            }
        }
    }

    /**
     * 注销服务
     * @param bool $useAsync
     */
    public function deregisterService($useAsync = true)
    {
        foreach ($this->consulConfig->getServiceConfigs() as $serviceConfig) {
            $serviceId = $serviceConfig->getId() ?? $serviceConfig->getName();
            $this->debug("注销Service：$serviceId");
            if ($useAsync) {
                $this->agent->deregisterService($serviceId);
            } else {
                $this->syncAgent->deregisterService($serviceId);
            }
        }
    }
}