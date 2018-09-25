<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Server\Handlers;

use Onion\Extra\Swoole\Tasks\Task;
use Swoole\Server;

class TaskHandler
{
    private $workers = [];
    public function __construct(array $workers)
    {
        $this->workers = $workers;
    }

    public function __invoke(Server $server, int $task, int $source, string $data)
    {
        /** @var Task $task */
        $task = \Swoole\Serialize::unpack($data);
        $name = $task->getName();

        assert(
            $this->workers[$name],
            new \UnexpectedValueException("No task worker registered for '{$name}")
        );

        $server->finish(\Swoole\Serialize::pack(
            $this->workers[$name]->run($task->getPayload()),
            1
        ));

        return;
    }
}
