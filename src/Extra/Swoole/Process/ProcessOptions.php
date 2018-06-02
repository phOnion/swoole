<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Process;

class ProcessOptions
{
    const PIPE_STREAM = 1;
    const PIPE_DGRAM = 2;

    private $daemon = false;
    private $muted = false;

    /**
     * Set if the process should run as a daemon or not
     */
    public function setDaemon(bool $state = true): void
    {
        $this->daemon = $state;
    }

    /**
     * Set whether or not stdin & stdout should be
     * provided to the process or not.
     */
    public function setMute(bool $state = true): void
    {
        $this->muted = $state;
    }

    /**
     * Check if the process should be run as a daemon
     */
    public function isDaemon(): bool
    {
        return $this->daemon;
    }

    /**
     * Return whether or not the stdin & stdout of the parent
     * process should be provided to the child
     */
    public function isMuted(): bool
    {
        return $this->muted;
    }

    /**
     * Return the type of the pipe to use based on whether the
     * process is muted or not
     */
    public function getPipeType(): int
    {
        return $this->isMuted() ? self::PIPE_DGRAM : self::PIPE_STREAM;
    }
}
