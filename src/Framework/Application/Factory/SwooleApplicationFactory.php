<?php declare(strict_types=1);
namespace Onion\Framework\Application\Factory;

use GuzzleHttp\Psr7\Response;
use Onion\Extra\Swoole\Process\Interfaces\ManagerInterface;
use Onion\Framework\Application\Application;
use Onion\Framework\Application\SwooleApplication;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;
use Swoole\Server;
use Swoole\Lock;

final class SwooleApplicationFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     *
     * @return Application
     */
    public function build(ContainerInterface $container): SwooleApplication
    {
        $lock = new Lock(
            Lock::FILELOCK,
            $container->has('application.lock') ?
                $container->get('application.lock') : sys_get_temp_dir() . '/' . md5_file(__FILE__)
        );

        if (!$lock->trylock()) {
            echo "Could not acquire lock, check 'application.lock'" . PHP_EOL;
            exit(1);
        }

        $server = $container->get(Server::class);
        // Prevent errors if task workers are set, but no callback is defined
        $server->on('start', $server->onStart ?? function () {});
        $server->on('finish', $server->onFinish ?? function () {});
        $server->on('task', $server->onTask ?? function () {});
        $server->on('request', $server->onRequest ?? function () {});

        if ($server instanceof \Swoole\WebSocket\Server) {
            $server->on('open', $server->onOpen ?? function (Server $server, \Swoole\Http\Request $request) {
                $server->close($request->fd);
            });
            $server->on('message', $server->onMessage ?? function () {});
            $server->on('close', $server->onClose ?? function () {});
        }

        return new SwooleApplication($server, $lock);
    }
}
