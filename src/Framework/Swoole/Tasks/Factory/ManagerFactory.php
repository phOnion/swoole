<?php declare(strict_types=1);
namespace Onion\Framework\Swoole\Tasks\Factory;

use Onion\Framework\Swoole\Tasks\Manager;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Swoole\Server as Swoole;

class ManagerFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        return new Manager($container->get(Swoole::class));
    }
}
