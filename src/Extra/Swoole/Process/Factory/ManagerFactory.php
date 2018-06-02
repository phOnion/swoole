<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Process\Factory;

use Onion\Extra\Swoole\Process\Manager;
use Onion\Extra\Swoole\Process\Process;
use Onion\Extra\Swoole\Process\ProcessOptions;
use Onion\Extra\Swoole\Process\WorkerInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Log\VoidLogger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ManagerFactory implements FactoryInterface
{
    public function build(ContainerInterface $container)
    {
        $manager = new Manager();
        $options = $container->has(ProcessOptions::class) ?
            $container->get(ProcessOptions::class) : new ProcessOptions();

        foreach ($container->get('workers') ?? [] as $name => $worker) {
            assert(
                isset($worker['class']),
                new \InvalidArgumentException("Missing 'class' field for worker definition")
            );

            $unit = $container->get($worker['class']);
            assert(
                $unit instanceof WorkerInterface,
                new \InvalidArgumentException("Worker must implement WorkerInterface")
            );

            $process = new Process(
                $name,
                $unit,
                $options
            );
            $logger = $container->has(\Psr\Log\LoggerInterface::class) ?
                $container->get(\Psr\Log\LoggerInterface::class) : new VoidLogger;
            $process->setLogger($logger);

            $channel = new \Swoole\Channel(($worker['buffer'] ?? 1024) * 1024);
            $manager->create($process, $worker['count'] ?? 1, $channel);
        }

        return $manager;
    }
}
