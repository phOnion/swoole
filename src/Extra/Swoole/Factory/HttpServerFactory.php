<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;
use Swoole\Http\Server;

final class HttpFactory implements FactoryInterface
{
    public function build(ContainerInterface $container): Server
    {
        $address = $container->has('application.server.address') ?
            $container->get('application.server.address') : '0.0.0.0';
        $port = $container->has('application.server.port') ?
            $container->get('application.server.port') : $this->getRandomPort($address);
        $options = $container->has('application.server.options') ?
            $container->get('application.server.options') : [];

        $type = SWOOLE_TCP;
        if (isset($options['ssl_cert_file']) || isset($options['ssl_key_file'])) {
            $type |= SWOOLE_SSL;
        }

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
