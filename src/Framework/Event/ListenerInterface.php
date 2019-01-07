<?php declare(strict_types=1);
namespace Onion\Framework\Event;

interface ListenerInterface
{
    /** @return string[] list of events handled by the listener */
    public function getEventNames(): array;

    /**
     * @var array $payload Event payload
     */
    public function trigger(array $payload = []): void;
}
