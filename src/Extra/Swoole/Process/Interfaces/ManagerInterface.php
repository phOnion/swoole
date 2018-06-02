<?php
declare(strict_types=1);
namespace Onion\Extra\Swoole\Process\Interfaces;

use Onion\Extra\Swoole\Process\Process;
use Swoole\Channel;

interface ManagerInterface
{
    const CHANNEL_INBOUND = "in";
    const CHANNEL_OUTBOUND = "out";

    /**
     * Start a $count instances of a process, provide an inbound data
     * channel for sending data to the process(es)
     *
     * @return Channel A channel for returning data from the process
     */
    public function create(Process $process, int $count = 1, Channel $channel = null): Channel;

    /**
     * Send a message to the process(es) identified by $name
     */
    public function message(string $name, $data): bool;

    /** Retrieve data from the process(es) identified by $name */
    public function read(string $name);

    /** Kill the process(es) identified by name */
    public function kill(string $name): void;
}
