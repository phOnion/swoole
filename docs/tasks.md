# Introduction

As you probably are familiar being able to offload heavy tasks outside of the current request is a very powerful way to make your application be fast, but most of the time it does require additional servers to run queues such as beanstalkd or others in order to achieve this, which automatically means that you will need to take care of that server as well.

By using swoole and by providing a single option in the `application.server.options` (`task_worker_num`) you get a queue that you can build upon using your existing codebase.

The swoole library offers both sync and async task scheduling, therefore this package provides wrapper for each

## Example Usage

### 1. Configuration

Lets create 4 additional worker processes that will be used to process our tasks to do so, add `'task_worker_num' => 4` to the `options` key under `application.server`.

Then in order to have something that will be handling your tasks, add to your configuration files:

```php
    'factories' => [
        ///
        Framework\Swoole\Tasks\ManagerInterface::class =>
            Framework\Swoole\Tasks\Factory\ManagerFactory::class,
        Framework\Swoole\Server\Handlers\TaskHandler::class =>
            Framework\Swoole\Server\Handlers\Factory\TaskHandlerFactory::class,
    ],
    'events' => [
        'task' => Framework\Swoole\Server\Handlers\TaskHandler::class,
    ],
```

By doing so you register the `TaskHandler` class that will be our dispatcher of our tasks. Now you are able to define workers for each individual tasks. To do so you can add use the `workers` key, which is a mapping of `'task.name' => ClassImplementingWorkerInterface`.

Defining a worker is as simple as:

```php

class MyWorker implements \Onion\Framework\Swoole\Tasks\WorkerInterface
{
    /** @var mixed $payload */
    public function run($payload)
    {
        sleep(10);
        echo "Do stuff\n";
    }
}
```

then register it as:

```php
return [
    // ...
    'workers' => [
        'my.task' => MyWorker::class,
    ],
    // ...
```

If you followed the steps so far you should have the absolute minimum necessary in order to make tasks work. From here all you need to do is, something similar to:

```php
class MyTaskMiddleware implements MiddlewareInterface
{
    private $tasks;
    public function __construct(ManagerInterface $taskManager)
    {
        $this->tasks = $taskManager;
    }

    public function handle(Request $request, RequestHandler $handler): Response
    {
        $myTask = new Task('my.task');
        $this->tasks->async($myTask)->then(function () {
            echo 'Task scheduled successfully';
        })->otherwise(function () {
            echo 'Task scheduling failed';
        });

        return $handler->process($request);
    }
}
```

When you register this middleware to a route and you refresh it, you should be able to see the request get processed and after about 10s see the messages in the server console.
