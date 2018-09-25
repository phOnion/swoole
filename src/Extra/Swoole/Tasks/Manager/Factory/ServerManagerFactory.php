<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks\Manager\Factory;

use Onion\Extra\Swoole\Tasks\Manager\Server;
use Onion\Extra\Swoole\Tasks\WorkerInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Swoole\Server as Swoole;

class ServerManagerFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        return new Server(
            $container->get(Swoole::class)
        );
    }
}
