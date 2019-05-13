<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/28
 * Time: 11:59
 */

namespace ESD\Plugins\Consul\ExampleClass;


use ESD\BaseServer\Server\Beans\Request;
use ESD\BaseServer\Server\Beans\Response;
use ESD\BaseServer\Server\Beans\WebSocketFrame;
use ESD\BaseServer\Server\ServerPort;
use ESD\Plugins\Consul\Services;

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
        print_r(Services::getServices("test"));
        $response->end("OK");
    }

    public function onWsMessage(WebSocketFrame $frame)
    {
        // TODO: Implement onWsMessage() method.
    }

    public function onWsOpen(Request $request)
    {
        // TODO: Implement onWsOpen() method.
    }

    public function onWsPassCustomHandshake(Request $request): bool
    {
        // TODO: Implement onWsPassCustomHandshake() method.
    }
}