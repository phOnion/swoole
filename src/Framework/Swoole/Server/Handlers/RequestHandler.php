<?php declare(strict_types=1);
namespace Onion\Framework\Swoole\Server\Handlers;

use function GuzzleHttp\Promise\queue;
use GuzzleHttp\Psr7\ServerRequest;
use Onion\Framework\Application\Application;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

class RequestHandler
{
    /** @var Application $application */
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function __invoke(Request $serverRequest, Response $response)
    {
        $_SERVER = array_change_key_case($serverRequest->server, CASE_UPPER) ?? [];
        $_GET = $serverRequest->get ?? [];
        $_POST = $serverRequest->post ?? [];
        $_COOKIE = $serverRequest->cookie ?? [];
        $_FILES = $serverRequest->files ?? [];

        $request = ServerRequest::fromGlobals();
        foreach ($serverRequest->header as $header => $line) {
            $request = $request->withHeader($header, $line);
        }

        try {
            /** @var ResponseInterface $result */
            $result = $this->application->handle($request);

            $response->status($result->getStatusCode());
            foreach ($result->getHeaders() as $header => $values) {
                $response->header($header, $result->getHeaderLine($header));
            }
            $response->end($result->getBody());
        } catch (\Throwable $exception) {
            $response->status(500);
            $response->header('Content-Type', 'text/plain; charset=utf-8');
            $response->end("Unexpected Server Error\n{$exception->getMessage()}:{$exception->getTraceAsString()}");
        } finally {
            go(function () {
                queue()->run();
            });
        }
    }
}
