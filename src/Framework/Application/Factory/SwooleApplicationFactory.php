<?php declare(strict_types=1);
namespace Onion\Framework\Application\Factory;

use Onion\Framework\Application\Application;
use Onion\Framework\Application\SwooleApplication;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;
use Swoole\Server;

final class SwooleApplicationFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     *
     * @return SwooleApplication
     */
    public function build(ContainerInterface $container): SwooleApplication
    {
        return new SwooleApplication($container->get(Server::class));
    }
}
