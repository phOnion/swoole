<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Server\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;
use Swoole\Http\Server;

final class HttpServerFactory implements FactoryInterface
{
    public function build(ContainerInterface $container): Server
    {
        $port = 0;
        $type = $container->has('application.server.type') ?
            $container->get('application.server.type') : SWOOLE_TCP;

        if ($container->has('port')) {
            $defaultAddress = in_array($type, [SWOOLE_UDP6, SWOOLE_TCP6]) ? '::1' : '127.0.0.1';

            $port = $container->get('application.server.port');
            $address = $container->has('application.server.address') ?
                $container->get('application.server.address') : $defaultAddress;
        }

        if ($container->has('application.server.sock')) {
            $defaultAddress = sys_get_temp_dir() . '/swoole-app.sock';
            $address = $container->get('application.server.sock');
            $port = 0;
        }

        $options = $container->has('application.server.options') ?
            $container->get('application.server.options') : [];
        $server = new \Swoole\Http\Server($address, $port, SWOOLE_PROCESS, $type);
        $server->set($options);

        return $server;
    }

    private function getRandomPort($address): int
    {
        while (true) {
            $port = mt_rand(1025, 65000);
            $fp = @fsockopen($address, $port, $errno, $errstr, 0.1);
            if ($fp) {
                fclose($fp);
                $fp = null;
                break;
            }
        }

        return $port;
    }
}
