<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks;

use Swoole\Server;

class Manager
{
    /** @var Server */
    private $server;

    public function __construct($server)
    {
        $this->server = $server;
    }

    public function push(Task $task)
    {
        @$this->server->task(
            \Swoole\Serialize::pack([
                'name' => $task->getName(),
                'payload' => $task->getPayload()
            ], 1),
            -1,
            function (Server $server, $source, $data) use ($task) {
                call_user_func($task->getCallback(), \Swoole\Serialize::unpack($data), $task->getName());
            }
        );
    }

    public function await(Task $task, float $timeout = 1)
    {
        return @$this->server->taskwait(\Swoole\Serialize::pack([
            'name' => $task->getName(),
            'payload' => $task->getPayload()
        ], 1), (double) $timeout);
    }

    public function parallel(array $tasks, float $timeout = 10)
    {
        $normalized = [];
        $results = [];

        foreach ($tasks as $idx => $task) {
            /** @var Task $task */
            $normalized[] = \Swoole\Serialize::pack([
                'name' => $task->getName(),
                'payload' => $task->getPayload()
            ], 1);
            $results["{$task->getName()}:{$idx}"] = false;
        }
        $result = @$this->server->taskWaitMulti($normalized, (double) $timeout);
        foreach ($result as $index => $value) {
            if (!isset($tasks[$index])) {
                "Key: $index????";
                continue;
            }
            $key = "{$tasks[$index]->getName()}:{$index}";
            $results[$key] = $value;
        }

        return $results;
    }
}
