<?php declare(strict_types=1);
namespace Onion\Framework\Swoole\Server\Handlers\Factory;

use Onion\Framework\Swoole\Server\Handlers\StartHandler;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;

class StartHandlerFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        $pid = $container->has('application.pid') ?
            $container->get('application.pid') : '/var/run/swoole_application.pid';

        return new StartHandler($container->get('application.server.addresses'), $pid);
    }
}
