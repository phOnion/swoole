<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks;

use Onion\Extra\Swoole\Tasks\Interfaces\ManagerInterface;
use Swoole\Server;

class Scheduler
{
    /** @var Manager */
    private $manager;
    /** @var Server */
    private $server;

    /**
     * @param Manager A task manager to process the task
     */
    public function __construct(Swerver $server, ManagerInterface $manager)
    {
        $this->server = $server;
        $this->manager = $manager;
    }

    /**
     * Schedule a task to run at $interval
     *
     * @param double $interval The interval in seconds at which to run the task
     * @param Task The task to execute
     *
     * @return int The timer identified needed later in order to stop execution
     */
    public function schedule(double $interval, Task $task): int
    {
        $payload = $task->getPayload();
        if (!is_array($payload)) {
            if ($payload === null) {
                $payload = [];
            }

            if ($payload instanceof \Iterator && !$payload instanceof \Generator) {
                $payload = iterator_to_array($payload);
            }

            if (is_object($payload)) {
                $payload = (new \ArrayObject($payload))->getArrayCopy();
            }

            if (is_scalar($payload)) {
                $payload = [$payload];
            }
        }

        return $this->server->tick($interval * 1000, function () use ($task) {
            $this->manager->push($task);
        }, $payload);
    }

    /**
     * Run a task after $interval
     *
     * @param double $interval The interval in seconds at which to run the task
     * @param Task The task to execute
     *
     * @return int The timer identified needed later in order to stop execution
     */
    public function delay(double $interval, Task $task): int
    {
        $payload = $task->getPayload();
        if (!is_array($payload)) {
            if ($payload === null) {
                $payload = [];
            }

            if ($payload instanceof \Iterator && !$payload instanceof \Generator) {
                $payload = iterator_to_array($payload);
            }

            if (is_object($payload)) {
                $payload = (new \ArrayObject($payload))->getArrayCopy();
            }

            if (is_scalar($payload)) {
                $payload = [$payload];
            }
        }

        return $this->server->after($interval * 1000, function () use ($task) {
            $this->manager->push($task);
        }, $payload);
    }

    /**
     * Unschedule a timer
     */
    public function stop(int $timer): bool
    {
        return $this->server->clearTimer($timerId);
    }
}
