<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Process;

use Swoole\Channel;
use Onion\Extra\Swoole\Process\Interfaces\ManagerInterface;

class Manager implements ManagerInterface
{
    /**
     * Registry of of the processes started by the manager as an assoc
     * array, grouped by process name.
     *
     * @var Process[][]
     */
    private $processes = [];

    /**
     * Registry of the channels available for communication
     * between the processes grouped by name. Note that if there is no
     * channel the value will be null.
     *
     * @var Channel[][]
     */
    private $channels = [];

    /**
     * Create and start a process running the callback provided in
     * $process. By default there is no communication channel between
     * the manager & the process(es) in order to allow messaging
     * an instance of \Swoole\Channel must be passed to as the third
     * argument. It will be used as a message queue between all the
     * processes started.
     *
     * On success it returns the outbound channel
     */
    public function create(Process $process, int $count = 1, Channel $channel = null): Channel
    {
        $out = new Channel(1024 * 1024);
        for ($i=0; $i<$count; $i++) {
            $proc = clone $process;
            $proc->start($channel, $out);
            $this->processes[$process->getName()][$proc->getPid()] = $proc;
        }

        $this->channels[$process->getName()] = [
            self::CHANNEL_INBOUND => $channel,
            self::CHANNEL_OUTBOUND => $out
        ];

        return $out;
    }

    /**
     * Send a message to the workers if a channel is provided during
     * process creation. If there is no channel provided or the insert
     * fails this method will `false`, `true` otherwise.
     *
     * @param string $name The name of the process(es) to send the message to
     * @param mixed $data The data to push in the queue
     */
    public function message(string $name, $data): bool
    {
        if (!$this->hasInboundChannel($name)) {
            return false;
        }

        return $this->channels[$name][self::CHANNEL_INBOUND]->push($data);
    }

    /**
     * Return the result of the process or null if no result is provided
     */
    public function read(string $name)
    {
        $result = $this->channels[$name][self::CHANNEL_OUTBOUND]->pop();

        return $result === false ? null : $result;
    }

    /**
     * Kill all instances of the the process identified by $name
     *
     * @param string $name The name of the process(es) to kill
     */
    public function kill(string $name): void
    {
        foreach ($this->processes[$name] ?? [] as $pid => $process) {
            $process->kill();
        }
    }

    /**
     * Terminate all processes started through the manager.
     */
    public function killAll(): void
    {
        foreach ($this->processes as $name => $pool) {
            $this->kill($name);
        }
    }

    /**
     * Whether a communication channel exists for the process(es) identified by $name
     *
     * @param string $name The name of the process(es)
     */
    public function hasInboundChannel(string $name): bool
    {
        return isset($this->channels[$name][self::CHANNEL_INBOUND]) ||
            $this->channels[$name][self::CHANNEL_INBOUND] !== null;
    }

    /**
     * Retrieve stats for a channel (if exists).
     *
     * @throws \InvalidArgumentException If no communication channel exists
     */
    public function statChannel(string $name, string $type = self::CHANNEL_INBOUND): ChannelStats
    {
        if ($type === self::CHANNEL_INBOUND && !$this->hasInboundChannel($name)) {
            throw new \InvalidArgumentException(
                "Channel for '{$name}' does not exist"
            );
        }

        if (!isset($this->channels[$name][$type])) {
            throw new \InvalidArgumentException(
                "Channel {$name}:{$type} does not exist"
            );
        }

        $stats = $this->channels[$name][$type]->stats();
        return new ChannelStats($stats['queue_num'], $stats['queue_bytes']);
    }
}
