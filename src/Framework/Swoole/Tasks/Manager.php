<?php declare(strict_types=1);
namespace Onion\Framework\Swoole\Tasks;

use GuzzleHttp\Promise\Promise;
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
     * Push a task to the server
     */
    public function async(Task $task): Promise
    {
        $payload = \Swoole\Serialize::pack($task, 1);
        $promise = new Promise();

        $this->server->task(
            $payload,
            -1,
            function (Swoole $server, $source, $data) use (&$promise, $task) {
                $result = \Swoole\Serialize::unpack($data);

                if ($result instanceof \Throwable) {
                    $promise->reject($data);
                    return;
                }

                $promise->resolve($data);
            }
        );

        return $promise;
    }

    public function sync(Task $task, int $timeout = 1): Promise
    {
        $payload = \Swoole\Serialize::pack($task, 1);
        $promise = new Promise(function () use (&$promise, $payload, $timeout) {
            $result = $this->server->taskwait($payload, (float) ($interval * 1000));
            if (!$result) {
                return $promise->reject($result);
            }

            $promise->resolve($result);
        });


        return $promise;
    }

    /**
     * Simultaneously send n+1 tasks to the server and wait $timeout
     * for their response. The results of the response are ordered in
     * the exact order at which they are provided, of a task does not
     * complete in time it's result will be false
     *
     */
    public function parallel(array $tasks, int $timeout = 10): Promise
    {
        $normalized = [];


        foreach ($tasks as $idx => $task) {
            /** @var Task $task */
            $normalized[] = \Swoole\Serialize::pack($task, 1);
        }

        $promise = new Promise(function () use (&$promise, $normalized, $timeout): void {
            $result = $this->server->taskWaitMulti($normalized, (float) ($timeout * 1000));

            foreach ($result as $value) {
                $individualPromise = clone $promise;
                if (!$value) {
                    $individualPromise->reject($value);
                    continue;
                }

                $individualPromise->resolve($value);
            }

            $promise->cancel();
        });

        return $promise;
    }

    public function schedule(Task $task, int $interval): Promise
    {
        $promise = null;
        $timer = $this->server->tick((float) ($interval * 1000), function () use (&$promise, $task) {
            $this->push($task)->then(function ($value) use ($promise) {
                $promise->resolve($value);
            }, function ($value) use ($promise) {
                $promise->reject($value);
            });
        });

        $promise = new Promise(function () use (&$promise) {
            $promise->reject();
        }, function () use ($timer) {
            $this->server->clearTimer($timer);
        });

        return $promise;
    }

    public function delay(Task $task, int $interval): Promise
    {
        $promise = null;
        $timer = $this->server->tick((float) ($interval * 1000), function () use (&$promise, $task) {
            $this->push($task)->then(function ($value) use ($promise) {
                $promise->resolve($value);
            }, function ($value) use ($promise) {
                $promise->reject($value);
            });
        });

        $promise = new Promise(function () {}, function () use ($timer) {
            $this->server->clearTimer($timer);
        });

        return $promise;
    }
}
