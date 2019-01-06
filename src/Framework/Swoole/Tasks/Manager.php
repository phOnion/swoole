<?php declare(strict_types=1);
namespace Onion\Framework\Swoole\Tasks;

use function GuzzleHttp\Promise\all;
use function GuzzleHttp\Promise\each;
use function GuzzleHttp\Promise\promise_for;
use function GuzzleHttp\Promise\rejection_for;
use function GuzzleHttp\Promise\task;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Onion\Framework\Swoole\Tasks\Interfaces\ManagerInterface;
use Onion\Framework\Swoole\Tasks\Task;
use Swoole\Server as Swoole;

class Manager implements ManagerInterface
{
    /** @var Swoole */
    private $server;

    /**
     * @param \Swoole\Server $server The server which will run the tasks
     */
    public function __construct(Swoole $server)
    {
        $this->server = $server;
    }

    /**
     * Push an async task to the queue. Returns a promise that will
     * resolve if the scheduling
     *
     * @return PromiseInterface
     */
    public function async(Task $task): PromiseInterface
    {
        return task(function () use ($task) {
            $result = $this->server->task($task, -1, function (Swoole $server, $worker, $result) {});

            return $result !== false ? promise_for(true) : rejection_for(false);
        });
    }

    public function sync(Task $task, int $timeout = 1): PromiseInterface
    {
        return task(function () use ($task, $timeout) {
            $result = $this->server->taskwait($task, (float) $timeout);

            if ($result === false) {
                $result = new \RuntimeException('Task failed');
            }

            if ($result instanceof \Throwable) {
                return rejection_for($result);
            }

            return promise_for($result);
        });
    }

    /**
     * Simultaneously send n+1 tasks to the server and wait $timeout
     * for their response. The results of the response are ordered in
     * the exact order at which they are provided, of a task does not
     * complete in time it's result will be false
     *
     */
    public function parallel(array $tasks, int $timeout = 10000): PromiseInterface
    {
        return task(function () use ($tasks, $timeout) {
            $results = $this->server->taskWaitMulti($tasks, (float) $timeout);
            if ($results === false) {
                return rejection_for($results);
            }

            $results = array_map(function ($result) {
                // $result = unserialize($result);
                if (!$result || $result instanceof \Throwable) {
                    return rejection_for($result);
                }

                return promise_for($result);
            }, $results);

            return each($results)->then(function () use ($results, $tasks) {
                $result = [];
                $keys = array_keys($tasks);
                foreach ($results as $index => $promise) {
                    $result[$keys[$index]] = $promise;
                }

                return array_values($result);
            });
        });
    }

    public function all(array $tasks, ?int $timeout = null): PromiseInterface
    {
        return $this->parallel($tasks, $timeout)
            ->then(function (array $tasks) {
                return all($tasks);
            });
    }

    public function race(array $tasks, ?int $timeout = null): PromiseInterface
    {
        return $this->parallel($tasks, $timeout)
            ->then(function ($result) {
                return array_shift($result);
            })->otherwise(function ($reason) {
                return array_shift($reason);
            });
    }

    public function schedule(Task $task, int $interval): PromiseInterface
    {
        $timer = null;
        $promise = new Promise(function () {}, function () use ($timer) {
            $this->server->clearTimer($timer);
        });

        $timer = $this->server->tick($interval, function () use ($task) {
            return $this->sync($task)->wait();
        });

        return $promise;
    }

    public function delay(Task $task, int $interval): PromiseInterface
    {
        $times = null;
        $promise = new Promise(null, function () use (&$timer) {
            $this->server->clearTimer($timer);
        });

        $timer = $this->server->after($interval, function () use ($task) {
            $this->async($task);
        });
    }
}
