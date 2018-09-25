<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Server\Handlers\Factory;

use Onion\Extra\Swoole\Server\Handlers\StartHandler;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Swoole\Lock;


class StartHandlerFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        $pid = $container->has('application.pid') ?
            $container->get('application.pid') : '/var/run/swoole.pid';

        $lock = new Lock(Lock::FILELOCK, $pid);

        if (!$lock->trylock()) {
            throw new \RuntimeException(
                "Unable to lock PID @ {$pid}"
            );
        }
        return new StartHandler($container->get('application.server.addresses'), $pid);
    }
}
