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
use function GuzzleHttp\Promise\queue;

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
     * resolve if the task has been scheduled successfully, but does
     * not guarantee completion.
     *
     * Calling `wait` on the returned promise will manually trigger
     * a tick and also does not guarantee if the task has been completed
     *
     * @var Task $task
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

    /**
     * Pushes a synchronous task to the queue, which will be triggered
     * whenever the next tick occurs, either during the request or at
     * the end of the current request AFTER sending the response to the
     * client.
     *
     * Calling `wait` on the returned promise will block the thread
     * until all pending promises are processed.
     */
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
     * @param Task[] $tasks
     * @param int $timeout The timeout after which the promises will be rejected
     *
     * @return PromiseInterface
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
                foreach ($results as $index => $promise) {
                    $result[$index] = $promise;
                }

                return $result;
            });
        });
    }

    /**
     * Sends multiple tasks to the queue at the same time and waits for
     * $timeout before automatically rejecting. If any of the tasks fail
     * this promise will be rejected
     *
     * @param Task[] $tasks The tasks to run
     * @param int $timeout The timeout of the tasks in ms
     *
     * @return PromiseInterface
     */
    public function all(array $tasks, int $timeout = 60000): PromiseInterface
    {
        return $this->parallel($tasks, $timeout)
            ->then(function (array $tasks) {
                return all($tasks);
            });
    }

    /**
     * Fire multiple tasks at the same time, whenever 1 finishes its result
     * will be the resolution or rejection of the returned promise.
     *
     * @param Task[] $tasks
     * @param int $timeout The timeout of the tasks in ms
     */
    public function race(array $tasks, int $timeout = 60000): PromiseInterface
    {
        return task(function () use ($tasks, $timeout) {
            $completed = false;
            $promise = new Promise();

            foreach ($tasks as $task) {
                go(function () use (&$completed, $promise, $task, $timeout) {
                    $this->sync($task, $timeout)->then(function ($value) use (&$completed, $promise) {
                        if (!$completed) {
                            $promise->resolve($value);
                            $completed = true;
                        }
                    })->otherwise(function ($reason) use (&$completed, $promise) {
                        if (!$completed) {
                            $promise->reject($reason);
                            $completed = true;
                        }
                    });
                });
            }

            return $promise;
        });
    }

    /**
     * Schedule a task to be executed every $interval in ms
     *
     * @param Task $task
     * @param int $interval The interval in ms after which to send the task
     *
     * @return PromiseInterface
     */
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

    /**
     * Send a delayed, will be sent after $interval in MS
     *
     * @param Task $task
     * @param int $interval Interval after which to send the task
     *
     * @return PromiseInterface
     */
    public function delay(Task $task, int $interval): PromiseInterface
    {
        $times = null;
        $promise = new Promise(null, function () use (&$timer) {
            $this->server->clearTimer($timer);
        });

        $timer = $this->server->after($interval, function () use ($task) {
            $this->async($task);
        });

        return $promise;
    }
}
