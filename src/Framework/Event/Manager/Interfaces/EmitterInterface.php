<?php declare(strict_types=1);
namespace Onion\Framework\Event\Manager\Interfaces;

use GuzzleHttp\Promise\PromiseInterface;

interface EmitterInterface
{
    public function emit(string $eventName, array $payload = []): PromiseInterface;
}
