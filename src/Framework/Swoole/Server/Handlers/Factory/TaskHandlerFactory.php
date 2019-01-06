<?php declare(strict_types=1);
namespace Onion\Framework\Swoole\Server\Handlers\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Swoole\Server\Handlers\TaskHandler;
use Onion\Framework\Swoole\Tasks\WorkerInterface;

class TaskHandlerFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        $workers = [];
        foreach ($container->get('workers') as $name => $worker) {
            $unit = $container->get($worker);
            assert(
                $unit instanceof WorkerInterface,
                new \InvalidArgumentException("Worker must implement WorkerInterface")
            );
            $workers[$name] = $unit;
        }

        return new TaskHandler($workers);
    }
}
