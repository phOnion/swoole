<?php declare(strict_types=1);
namespace Onion\Framework\Event\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Event\ListenerInterface;
use Onion\Framework\Event\EventHandler;

class EventHandlerFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        $listeners = [];
        foreach ($container->get('listeners') as $handler) {
            $handlerObject = $container->get($handler);
            assert(
                $handlerObject instanceof ListenerInterface,
                new \LogicException("{$handler} does not implement ListenerInterface")
            );

            foreach ($handlerObject->getEventNames() as $event) {
                if (!isset($listeners[$event])) {
                    $listeners[$event] = [];
                }

                $listeners[$event][] = $handlerObject;
            }
        }

        return new EventHandler($listeners);
    }
}
