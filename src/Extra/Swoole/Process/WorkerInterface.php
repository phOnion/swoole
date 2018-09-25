<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Process;

use Onion\Extra\Swoole\Tasks\WorkerInterface as TaskWorker;

interface WorkerInterface extends TaskWorker
{
    /**
     * Returns an interval in seconds at which the worker will poll
     * the channel of data to process. In order to avoid high CPU
     * usage this should be set toa non-zero value.
     *
     * For heavy load workers or such that need to process tasks
     * "instantly" this method can return 0.01
     */
    public function getPollInterval(): float;
}
