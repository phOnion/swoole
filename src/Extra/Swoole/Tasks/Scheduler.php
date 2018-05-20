<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks;

use Swoole\Server;

class Scheduler
{
    /** @var Server */
    private $server;

    public function __construct($server)
    {
        $this->server = $server;
    }

    public function schedule(int $seconds, Task $task, bool $onetime = false): int
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

        return call_user_func(
            [$this->server, $onetime ? 'after' : 'tick'],
            $seconds * 1000,
            $task->getCallback(),
            $payload
        );
    }

    public function stop(int $timerId): bool
    {
        return $this->server->clearTimer($timerId);
    }
}
