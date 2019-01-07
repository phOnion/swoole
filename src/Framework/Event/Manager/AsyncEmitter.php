<?php declare(strict_types=1);
namespace Onion\Framework\Event\Manager;

use Onion\Framework\Event\Manager\Interfaces\EmitterInterface;
use Onion\Framework\Swoole\Tasks\Interfaces\ManagerInterface as TaskManager;
use Onion\Framework\Swoole\Tasks\Task;
use GuzzleHttp\Promise\PromiseInterface;


class AsyncEmitter implements EmitterInterface
{
    /** @var TaskManager $tasks */
    private $tasks;

    public function __construct(TaskManager $tasks)
    {
        $this->tasks = $tasks;
    }

    public function emit(string $event, array $payload = []): PromiseInterface
    {
        $task = new Task('event', [
            $event,
            $payload
        ]);

        return $this->tasks->async($task);
    }
}
