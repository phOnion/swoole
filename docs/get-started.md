# Tutorial

## Introduction

### 1. Installing the extension

Here you have 2 options:

1. Install it through PECL via - `pecl install swoole`; or
2. Compile it from source. While this is more advanced way of getting things done, it allows for more control over what functionality is enabled. Like for example: the `--enable-sockets` flag that will enable the built-in web socket server, `--enable-http2` for HTTP2 and other features.

### 2. Port application

In order to port an existing application you will need to do 3 things:

1. Add a new configuration section in your configs; and

```php
    'application' => [
        'type' => \Swoole\Http\Server::class, // For the purpose of this tutorial, but can be \Swoole\WebSocket\Server too
        'server' => [ // Options used to bootstrap the server itself
            'type' => SWOOLE_PROCESS, // the mode of the server, also SWOOLE_BASE or SWOOLE_THREAD
            'addresses' => [ // List of addresses on which the server should listen
                // For more details see:
                // https://secure.php.net/manual/en/swoole-server.addlistener.php
                // https://www.swoole.co.uk/docs/modules/swoole-server-methods
                ['address' => '0.0.0.0', 'port' => 1337, 'type' => SWOOLE_TCP | SWOOLE_SSL],
                // More addresses on which to listen...
            ],
            'options' => [
                /**
                 * this is a list of options that is directly passed to
                 * the server. See https://www.swoole.co.uk/docs/modules/swoole-server/configuration
                 * as well as any server-specific configurations.
                 */
                'worker_num' => 4, // Spawn 4 processes to process requests
            ],
            'events' => [
                /**
                 * This section is dedicated to setting the handlers for the different
                 * events that can happen on the server itself. See
                 * https://www.swoole.co.uk/docs/modules/swoole-server/callback-functions
                 * For detailed list of events
                 */
                'start' => Framework\Swoole\Server\Handlers\StartHandler::class, // Built-in class that announces the registered listeners
                'request' => Framework\Swoole\Server\Handlers\RequestHandler::class, // Handles all incoming requests
            ],
        ],
    ],
```

2. Modify your `factories` section to register the new factories of this package.

```php
// Example from the tutorial of the main application
        'factories' => (object) [
            Framework\Application\Application::class => // Existing mapping to application
                Framework\Application\Factory\ApplicationFactory::class,
            // New entries relevant to this library
            Framework\Application\Interfaces\ApplicationInterface::class =>
                Framework\Application\Factory\SwooleApplicationFactory::class,
            \Swoole\Server::class =>
                Framework\Swoole\Server\Factory\ServerFactory::class,
            Framework\Swoole\Handlers\StartHandler::class =>
                Framework\Swoole\Handlers\Factory\StartHandlerFactory::class,
        ],
```

***NOTE** it is advisable that you add `\Swoole\Server` to the `shared` section in
order to ensure that new server instances are not created when you need to utilize
functionality such as `Swoole\Tasks\Manager` (which also makes sense to be added as
 shared dependency).*

3. Change the class which you use to run the application.

```php
$app = $container->get(Framework\Application\Interfaces\ApplicationInterface::class); // We fetch the interface, not the implementation mapping
$app->run(); // We do not use `GuzzleHttp\Psr7\ServerRequest::fromGlobals()` here
```

### 3. Profit

That is all, you are all set! No further changes should be needed and your application should be good to go. Now the only thing left would be to move those blocking operations inside your code to [tasks](./tasks) or [Coroutines](https://www.swoole.co.uk/coroutine)
