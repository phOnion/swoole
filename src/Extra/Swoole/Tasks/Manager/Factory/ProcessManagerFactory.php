<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks\Manager\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;
use Onion\Extra\Swoole\Tasks\Manager\Process;
use Onion\Extra\Swoole\Process\Interfaces\ManagerInterface;

class ProcessManagerFactory implements FactoryInterface
{
    public function build(ContainerInterface $container)
    {
        return new Process(
            $container->get(ManagerInterface::class)
        );
    }
}
