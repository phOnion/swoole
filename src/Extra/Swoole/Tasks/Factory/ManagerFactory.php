<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Extra\Swoole\Tasks\Manager;
use Onion\Extra\Swoole\Tasks\WorkerInterface;
use Swoole\Server;

class ManagerFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        /** @var Server $server */
        $server = $container->get(Server::class);
        $server->on('finish', function () {
            // Nothing to do
        });
        $server->on('task', function (Server $server, int $task_id, int $source, string $data) use ($container) {
            $data = \Swoole\Serialize::unpack($data);

            if (!isset($data['name'])) {
                return;
            }

            $worker = "workers.{$data['name']}";
            if ($container->has($worker)) {
                /** @var WorkerInterface $unit */
                $unit = $container->get($worker);
                if (is_string($unit)) {
                    $unit = $container->get($unit);
                }

                assert(
                    $unit instanceof WorkerInterface,
                    new \UnexpectedValueException("No task worker registered for '{$data['name']}")
                );

                $result = $unit->run($data['payload']);
                $server->finish(\Swoole\Serialize::pack($result, 1));
                return $result;
            }
            $server->finish();
        });

        return new Manager($server);
    }
}
