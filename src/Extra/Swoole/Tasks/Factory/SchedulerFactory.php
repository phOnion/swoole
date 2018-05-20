<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Extra\Swoole\Tasks\Scheduler;
use Onion\Extra\Swoole\Tasks\WorkerInterface;
use Swoole\Server;

class SchedulerFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        /** @var Server $server */
        $server = $container->get(Server::class);

        return new Scheduler($server);
    }
}
