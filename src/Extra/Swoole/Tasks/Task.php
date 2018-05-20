<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks;

class Task
{
    private $name;
    private $payload = null;

    public function __construct(string $name, callable $callback = null)
    {
        $this->name = $name;
        $this->callback = $callback ?? function () {
            // Nothing to do
        };
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function withPayload($payload): void
    {
        $this->payload = $payload;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function withCallback(callable $callback): void
    {
        $this->callback = $callback;
    }

    public function getCallback(): callable
    {
        return $this->callback;
    }
}
