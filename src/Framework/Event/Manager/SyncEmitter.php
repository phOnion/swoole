<?php declare(strict_types=1);
namespace Onion\Framework\Event\Manager;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Onion\Framework\Event\EventHandler;
use Onion\Framework\Event\Manager\Interfaces\EmitterInterface;

class SyncEmitter implements EmitterInterface
{
    /** @var EventHandler $eventHandler */
    private $eventHandler;

    public function __construct(EventHandler $handler)
    {
        $this->eventHandler = $handler;
    }

    public function emit(string $event, array $payload = []): PromiseInterface
    {
        $promise = new Promise();
        try {
            if ($this->eventHandler->run([$event, $payload])) {
                $promise->resolve(true);
            } else {
                $promise->reject(false);
            }
        } catch (\Exception $ex) {
            $promise->reject($ex);
        }

        return $promise;
    }
}
