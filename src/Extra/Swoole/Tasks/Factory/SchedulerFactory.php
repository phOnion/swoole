<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks\Factory;

use Onion\Extra\Swoole\Tasks\Interfaces\ManagerInterface;
use Onion\Extra\Swoole\Tasks\Scheduler;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Swoole\Server;

class SchedulerFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        /** @var Server $server */
        $server = $container->get(Server::class);

        return new Scheduler($server, $container->get(ManagerInterface::class));
    }
}
