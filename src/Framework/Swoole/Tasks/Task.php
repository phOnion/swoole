<?php declare(strict_types=1);
namespace Onion\Framework\Swoole\Tasks;

class Task
{
    /** @var string $name */
    private $name;
    /** @var mixed $payload */
    private $payload = null;

    public function __construct(string $name, array $payload = [])
    {
        $this->name = $name;
        $this->payload = $payload;
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
     * @param mixed $payload
     */
    public function withPayload($payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Retrieve the payload of the task
     *
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
