<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks\Manager\Factory;

use Onion\Extra\Swoole\Tasks\Manager\Server;
use Onion\Extra\Swoole\Tasks\WorkerInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Swoole\Server as Swoole;

class ServerManagerFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        /** @var Swoole $server */
        $server = $container->get(Swoole::class);
        $server->on('finish', function () {
            // Nothing to do
        });
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
        $server->on('task', function (Swoole $server, int $task_id, int $source, string $data) use ($workers) {
            $data = \Swoole\Serialize::unpack($data);

            if (!isset($data['name'])) {
                return;
            }
            $name = $data['name'];
            assert(
                $workers[$name],
                new \UnexpectedValueException("No task worker registered for '{$name}")
            );

            $result = $workers[$name]->run($data['payload']);
            $server->finish(\Swoole\Serialize::pack($result, 1));

            return $result;
        });

        return new Server($server);
    }
}
