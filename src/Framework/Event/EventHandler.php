<?php declare(strict_types=1);
namespace Onion\Framework\Event;

use Onion\Framework\Swoole\Tasks\WorkerInterface;

class EventHandler implements WorkerInterface
{
    private $listeners = [];

    /**
     * @var ListenerInterface[][] $listeners
     */
    public function __construct(array $listeners)
    {
        $this->listeners = $listeners;
    }

    public function run($payload)
    {
        list($event, $payload)=$payload;

        $eventPattern = '~^' . str_replace('*', '.*', $event) . '$~i';
        reset($this->listeners);
        foreach ($this->listeners as $event => $listeners) {
            if (preg_match($eventPattern, $event) === 1) {
                try {
                    reset($listeners);
                    /** @var \SplStack $listeners */
                    foreach ($listeners as $index => $listener) {
                        $listener->trigger($payload ?? []);
                    }

                    return true;
                } catch (Exception\InterruptException $ex) {
                    break;
                }
            }
        }

        return false;
    }
}
