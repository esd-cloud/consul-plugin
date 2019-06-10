<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/28
 * Time: 11:59
 */

namespace ESD\Plugins\Consul\ExampleClass;

use ESD\Core\Server\Beans\Request;
use ESD\Core\Server\Beans\Response;
use ESD\Core\Server\Beans\WebSocketFrame;
use ESD\Core\Server\Port\ServerPort;
use ESD\Psr\Cloud\Services;

class ConsulPort extends ServerPort
{

    public function onTcpConnect(int $fd, int $reactorId)
    {
        // TODO: Implement onTcpConnect() method.
    }

    public function onTcpClose(int $fd, int $reactorId)
    {
        // TODO: Implement onTcpClose() method.
    }

    public function onTcpReceive(int $fd, int $reactorId, string $data)
    {
        // TODO: Implement onTcpReceive() method.
    }

    public function onUdpPacket(string $data, array $client_info)
    {
        // TODO: Implement onUdpPacket() method.
    }

    public function onHttpRequest(Request $request, Response $response)
    {
        /** @var Services $service */
        $service = DIGet(Services::class);
        print_r($service->getServices("esd"));
        $response->end("OK");
    }

    public function onWsOpen(Request $request)
    {
        // TODO: Implement onWsOpen() method.
    }

    public function onWsPassCustomHandshake(Request $request): bool
    {
        // TODO: Implement onWsPassCustomHandshake() method.
    }

    public function onWsClose(int $fd, int $reactorId)
    {
        // TODO: Implement onWsClose() method.
    }

    public function onWsMessage(WebSocketFrame $frame)
    {
        // TODO: Implement onWsMessage() method.
    }
}