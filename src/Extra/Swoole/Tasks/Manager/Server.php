<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks\Manager;

use Onion\Extra\Swoole\Tasks\Interfaces\ManagerInterface;
use Onion\Extra\Swoole\Tasks\Task;
use Swoole\Server as Swoole;

class Server implements ManagerInterface
{
    /** @var Server */
    private $server;

    /**
     * @param \Swoole\Server $server The server which will run the tasks
     */
    public function __construct($server)
    {
        $this->server = $server;
    }

    /**
     * Push a task to the server
     */
    public function push(Task $task): bool
    {
        $payload = \Swoole\Serialize::pack($task, 1);

        return (bool) $this->server->task(
            $payload,
            -1,
            function (Swoole $server, $source, $data) use ($task) {
                call_user_func($task->getCallback(), $server, \Swoole\Serialize::unpack($data));
            }
        );
    }

    /**
     * Send a task to the workers and wait for the result until $timeout
     * is reached.
     *
     * @return bool|mixed The result of the task if success or false on timeout
     */
    public function await(Task $task, float $timeout = 1)
    {
        $payload = \Swoole\Serialize::pack([
            'name' => $task->getName(),
            'payload' => $task->getPayload()
        ], 1);
        return $this->server->taskwait($payload, (double) $timeout);
    }

    /**
     * Simultaneously send n+1 tasks to the server and wait $timeout
     * for their response. The results of the response are ordered in
     * the exact order at which they are provided, of a task does not
     * complete in time it's result will be false
     *
     */
    public function parallel(array $tasks, float $timeout = 10): array
    {
        $normalized = [];
        $results = [];

        foreach ($tasks as $idx => $task) {
            /** @var Task $task */
            $normalized[] = \Swoole\Serialize::pack([
                'name' => $task->getName(),
                'payload' => $task->getPayload()
            ], 1);
            $results[$task->getName()] = false;
        }
        $result = $this->server->taskWaitMulti($normalized, (double) $timeout);
        foreach ($result as $index => $value) {
            $results[$tasks[$index]->getName()] = $value;
        }

        return $results;
    }
}
