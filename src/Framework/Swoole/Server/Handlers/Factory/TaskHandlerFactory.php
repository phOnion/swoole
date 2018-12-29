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
            assert(
                isset($worker['class']),
                new \InvalidArgumentException("Missing 'class' field for worker definition")
            );
            $unit = $container->get($worker['class']);
            assert(
                $unit instanceof WorkerInterface,
                new \InvalidArgumentException("Worker must implement WorkerInterface")
            );
            $workers[$name] = $unit;
        }

        return new TaskHandler($workers);
    }
}
