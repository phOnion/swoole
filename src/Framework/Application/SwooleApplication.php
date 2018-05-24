<?php declare(strict_types=1);
namespace Onion\Framework\Application;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use GuzzleHttp\Psr7\ServerRequest;
use Onion\Framework\Application\Interfaces\ApplicationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SwooleApplication implements ApplicationInterface
{
    /** @var \Swoole\Http\Server */
    private $server;

    /** @var RequestHandlerInterface */
    private $requestHandler;

    public function __construct(Server $server, RequestHandlerInterface $requestHander)
    {
        $this->server = $server;
        $this->requestHandler = $requestHander;
    }

    public function run(): void
    {
        $app = $this->requestHandler;

        // Prevent errors if task workers are set, but no callback is defined
        $this->server->on('finish', $this->server->onFinish ?? function () {
        });
        $this->server->on('task', $this->server->onTask ?? function () {
        });
        $this->server->on('start', function (Server $server) use ($app) {
            echo "Starting application server on {$server->host}:{$server->port}" . PHP_EOL;
        });

        $this->server->on('request', function (Request $request, Response $response) use ($app) {
            $_SERVER = [];
            foreach ($request->server as $name => $value) {
                $_SERVER[strtoupper($name)] = $value;
            }
            $_GET = $request->get ?? [];
            $_POST = $request->post ?? [];
            $_COOKIE = $request->cookie ?? [];
            $_FILES = $request->files ?? [];

            $headers = $request->header;
            $request = ServerRequest::fromGlobals();
            foreach ($headers as $header => $line) {
                $request = $request->withHeader($header, $line);
            }

            try {
                /** @var ResponseInterface $result */
                $result = $app->handle($request);

                $response->status($result->getStatusCode());
                foreach ($result->getHeaders() as $header => $values) {
                    $response->header($header, $result->getHeaderLine($header));
                }
                $response->end($result->getBody());
            } catch (\Throwable $exception) {
                $response->status(500);
                $response->header('Content-Type', 'text/plain; charset=utf-8');
                $response->end('Unexpected Server Error');
            }
        });
        $this->server->start();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw new \BadMethodCallException(
            'Should not be used as request handle'
        );
    }
}
