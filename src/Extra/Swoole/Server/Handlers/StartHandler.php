<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Server\Handlers;

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

    private $servers = [];
    private $pidFile;

    public function __construct(array $applicationServerAddresses, string $pidFile)
    {
        $this->servers = $applicationServerAddresses;
        $this->pidFile = $pidFile;
    }

    public function __invoke(Server $server)
    {
        echo "Listening on: " . PHP_EOL;
        file_put_contents($this->pidFile, (int) $server->master_pid);

        foreach($this->servers as $serv) {
            $type = self::SERVER_TYPES[$serv['type']] ??
                $serv['type'];
            $schema = 'http';

            switch ($serv['type']) {
                case SWOOLE_SOCK_UNIX_DGRAM:
                case SWOOLE_SOCK_UNIX_STREAM:
                    echo "\t - file://{$serv['address']}";
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
                    echo "\t - {$schema}://{$serv['address']}:{$serv['port']}" . PHP_EOL;
                    break;
                default:
                    echo "\t - {$serv['address']}:{$serv['port']}" . PHP_EOL;
                    break;
            }
        }
    }
}
