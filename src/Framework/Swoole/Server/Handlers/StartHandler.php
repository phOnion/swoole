<?php declare(strict_types=1);
namespace Onion\Framework\Swoole\Server\Handlers;

use Onion\Framework\Console\Buffer;
use Onion\Framework\Console\Console;
use Swoole\Server;

class StartHandler
{
    private const SERVER_TYPES = [
        SWOOLE_TCP => 'tcp',
        SWOOLE_TCP6 => 'tcp6',
        SWOOLE_UDP => 'udp',
        SWOOLE_UDP6 => 'udp6',
        SWOOLE_SOCK_UNIX_DGRAM => 'unix_dgram',
        SWOOLE_SOCK_UNIX_STREAM => 'unix_stream',
    ];

    /** @var array $servers */
    private $servers = [];
    /** @var string $pidFile */
    private $pidFile;

    public function __construct(array $applicationServerAddresses, string $pidFile)
    {
        $this->servers = $applicationServerAddresses;
        $this->pidFile = $pidFile;
    }

    public function __invoke(Server $server)
    {
        if (class_exists(\Swoole\Runtime::class) && method_exists(\Swoole\Runtime::class, 'enableCoroutine')) {
            \Swoole\Runtime::enableCoroutine(true);
        }

        $console = new Console(new Buffer('php://stdout'));
        $console->writeLine('%text:cyan%Listening on:');
        file_put_contents($this->pidFile, (int) $server->master_pid);

        foreach($this->servers as $serv) {
            $schema = 'http';

            switch ($serv['type'] ?? SWOOLE_TCP) {
                case SWOOLE_SOCK_UNIX_DGRAM:
                case SWOOLE_SOCK_UNIX_STREAM:
                    $console->writeLine("\t %text:green%file://{$serv['address']}");
                    break;
                case SWOOLE_TCP | SWOOLE_SSL:
                case SWOOLE_TCP6 | SWOOLE_SSL:
                case SWOOLE_UDP | SWOOLE_SSL:
                case SWOOLE_UDP6 | SWOOLE_SSL:
                    $schema = 'https';
                case SWOOLE_TCP:
                case SWOOLE_TCP6:
                case SWOOLE_UDP:
                case SWOOLE_UDP6:
                    $console->writeLine("\t %text:green%{$schema}://{$serv['address']}:{$serv['port']}");
                    break;
                default:
                    $console->writeLine("\t %text:green%{$serv['address']}:{$serv['port']}");
                    break;
            }
        }
    }
}
