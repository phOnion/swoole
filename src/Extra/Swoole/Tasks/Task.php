<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks;

class Task
{
    private $name;
    private $payload = null;

    /**
     * Create a task class that will be sent to a server
     * for execution.
     *
     * The last argument is optional since a task does not need to have
     * a callback when used in combination with the Task\Manager as then
     * it might purely serve as a message to a worker, rather than a task
     * on itself while it is required when used with Task\Scheduler
     *
     * @param string $name An alias of the task
     * @param string
     */
    public function __construct(string $name, callable $callback = null)
    {
        $this->name = $name;
        $this->callback = $callback;
    }

    /**
     * Retrieve the name of the task
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the payload to pass when executing the task
     */
    public function withPayload($payload): void
    {
        $this->payload = $payload;
    }

    /**
     * Retrieve the payload of the task
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Set an additional callback to execute after the process is finished
     */
    public function withCallback(callable $callback): void
    {
        $this->callback = $callback;
    }

    /**
     * Retrieve the callback to execute
     */
    public function getCallback(): callable
    {
        return $this->callback  ?? function () {
            // Nothing to do
        };
    }
}
