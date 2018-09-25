<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Server\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;
use Swoole\Server;
use Swoole\Lock;

final class ServerFactory implements FactoryInterface
{
    private const DEFAULT_INTERFACE = '127.0.0.1';

    public function build(ContainerInterface $container): Server
    {
        if (!defined('SWOOLE_SSL')) {
            define(SWOOLE_SSL, 0);
        }

        $servers = $container->get('application.server.addresses');

        $class = $container->get('application.type');
        $port = $this->getRandomPort(self::DEFAULT_INTERFACE);

        $options = $container->has('application.server.options') ?
            $container->get('application.server.options') : [];

        /** @var \Swoole\Server $server */
        $server = new $class(
            self::DEFAULT_INTERFACE,
            $port,
            $container->has('application.server.mode') ? $container->get('application.server.mode') : SWOOLE_BASE,
            isset($options['ssl_cert_file']) ? SWOOLE_TCP | SWOOLE_SSL : SWOOLE_TCP
        );
        echo 'Instantiated internal listener @ ' . self::DEFAULT_INTERFACE . ":{$port}" . PHP_EOL;

        foreach ($servers as $config) {
            $server->addListener($config['address'], $config['port'], $config['type']);
        }

        $server->set($options);
        if ($container->has('application.server.events')) {
            foreach ($container->get('application.server.events') as $event => $handler) {
                $handler = $container->get($handler);
                if (!is_callable($handler)) {
                    throw new \RuntimeException(
                        "Provided server event handler for '{$event}' is not callable"
                    );
                }

                $server->on($event, $handler);
            }
        }

        return $server;
    }

    private function getRandomPort($address): int
    {
        $used = [];
        while (true) {
            $port = mt_rand(1025, 65000);
            if (in_array($port, $used)) {
                continue;
            }

            $fp = @fsockopen($address, $port, $errno, $errstr, 0.1);
            if (!$fp) {
                break;
            }
            $used[] = $port;
        }

        return $port;
    }
}
