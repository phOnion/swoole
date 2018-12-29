<?php declare(strict_types=1);
namespace Onion\Framework\Application;

use Onion\Framework\Application\Interfaces\ApplicationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Server;

class SwooleApplication implements ApplicationInterface
{
    /** @var \Swoole\Server */
    private $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function run(): void
    {
        $this->server->start();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw new \LogicException(
            'Should not be used as request handle'
        );
    }
}
