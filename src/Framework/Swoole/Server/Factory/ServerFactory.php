<?php declare(strict_types=1);
namespace Onion\Framework\Swoole\Server\Factory;

use Onion\Framework\Console\Console;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;
use Swoole\Server;
use Onion\Framework\Console\Buffer;

final class ServerFactory implements FactoryInterface
{
    private const DEFAULT_INTERFACE = '127.0.0.1';

    public function build(ContainerInterface $container): Server
    {
        if (!defined('SWOOLE_SSL')) {
            define(SWOOLE_SSL, 0);
        }

        $console = new Console(new Buffer('php://stdout'));

        $servers = $container->get('application.server.addresses');

        $class = $container->get('application.type');
        $port = $this->getRandomPort();

        $options = $container->has('application.server.options') ?
            $container->get('application.server.options') : [];

        /** @var \Swoole\Server $server */
        $server = new $class(
            self::DEFAULT_INTERFACE,
            $port,
            $container->has('application.server.mode') ? $container->get('application.server.mode') : SWOOLE_BASE,
            isset($options['ssl_cert_file']) ? SWOOLE_TCP | SWOOLE_SSL : SWOOLE_TCP
        );
        $console->writeLine(
            '%text:cyan%Instantiated internal listener @ %text:green%' . self::DEFAULT_INTERFACE . ":{$port}"
        );

        foreach ($servers as $config) {
            $server->addListener($config['address'], $config['port'], $config['type'] ?? SWOOLE_TCP);
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

    private function getRandomPort(): int
    {
        $used = [];
        while (true) {
            $port = mt_rand(1025, 65000);
            if (in_array($port, $used)) {
                continue;
            }

            $fp = @socket_create_listen($port, 32);
            if (is_resource($fp)) {
                socket_close($fp);
                break;
            }
            $used[] = $port;
        }

        return $port;
    }
}
