# Swoole compatibility layer for `onion/framework`

This is a minimal wrapper that handles the creation of the `Swoole\Http\Server` and it's
initialization and allows any application already written using `onion/framework` to
"just work" with the app server.

## Configuration

This package's default factories require minor configuration additions
in order to get up and running:

### Config

```php
<?php
### Configuration
return [
    'application' => [
        'server' => [
            'address' => '', // The address on which to bind the app server. Binds on '0.0.0.0' if none is provided
            'port' => 1234, // The port on which the server to listen. A random one will be used if none is provided
            'options' => [ // A list of options that is being passed directly to `Swoole\Http\Server::set()` for configuration

            ]
        ]
    ]
];
```

### Dependencies

```php
<?php
return [
    'factories' => [
        // ... Definitions
        // The base application is still in use, just make sure to register it directly as class name
        \Onion\Framework\Application\Application::class => \Onion\Framework\Application\Factory\ApplicationFactory::class,
        // The app server
        \Swoole\Http\Server::class => \Onion\Extra\Swoole\Factory\HttpServerFactory::class,
        // The definition of the application wrapper
        \Onion\Framework\Application\Interfaces\ApplicationInterface::class => \Onion\Framework\Application\Factory\SwooleApplicationFactory::class,
        // ... More definitions
    ],
];
```

Note that we assume that you have something similar to the following in your `index.php` file:

```php
<?php

// Bootstrap

$container->get(\Onion\Framework\Application\Interfaces\ApplicationInterface::class)
    ->run(); // Note the ServerRequest::fromGlobals() is not necessary here, so it can be omitted
```
