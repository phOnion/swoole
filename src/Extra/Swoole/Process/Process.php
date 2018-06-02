<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Process;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Onion\Extra\Swoole\Tasks\Task;

class Process implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    /**
     * An alias of the process to use when using the process manager
     * @var string
     */
    private $name;

    /**
     * The instance of the \Swoole\Process that is wrapped
     * @var \Swoole\Process
     */
    private $process;

    /**
     * The worker that will get invoked on process start
     * @var WorkerInterface
     */
    private $worker;

    /**
     * Configuration object for the process
     * @var ProcessOptions
     */
    private $options;

    /**
     * The communication channel (if any) for the process
     * @var \Swoole\Channel */
    private $channel;

    /**
     * @param string $name Alias of the process
     * @param WorkerInterface $worker The worker to execute
     * @param ProcessOptions Configuration for the process
     */
    public function __construct(string $name, WorkerInterface $worker, ProcessOptions $options)
    {
        $this->name = $name;
        $this->options = $options;
        $this->worker = $worker;
    }

    /**
     * Return the alias of the process
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Start the process
     */
    public function start(\Swoole\Channel $in, \Swoole\Channel $out)
    {
        $options = $this->options;
        /** @var WorkerInterface */
        $worker = $this->worker;
        $this->process = new \Swoole\Process(function (\Swoole\Process $proc) use ($worker, $in, $out) {
            while (true) {
                try {
                    $task = $in->pop();
                    if (!$task instanceof Task) {
                        // Convert to seconds
                        usleep((int) $worker->getPollInterval() * 1000000);
                        continue;
                    }
                    $this->log(LogLevel::DEBUG, "Waking up {$this->getName()}({$proc->pid})");
                    $result = $worker->run($task->getPayload());
                    $out->push($result);
                    $out->push(mt_rand(0, 5));

                    ($task->getCallback())($result);
                } catch (\Throwable $ex) {
                    $this->log(
                        LogLevel::ERROR,
                        "Exception '" . get_class($ex) . "' ocurred in process '{$this->getName()}'." .
                            " Message: '{$ex->getMessage()}"
                    );
                }
            }
        }, $options->isMuted(), $options->getPipeType());

        $this->process->start();
        $this->log(LogLevel::DEBUG, "Started process '{$this->getName()}' - PID: {$this->getPid()}");
    }

    /**
     * Attempt to kill the process. Handles termination gracefully
     * by first attempting to send SIGTERM and if it fails - SIGKILL
     * is issued
     *
     * @return bool If the process was killed successfully
     */
    public function kill(): bool
    {
        if ($this->getPid() === null) {
            throw new \LogicException("Can't kill not running process");
        }

        $this->log(LogLevel::DEBUG, "Sending SIGTERM to {$this->getName()}");
        if (!$this->process->kill($this->getPid(), SIGTERM)) {
            $this->log(LogLevel::INFO, "Sending SIGKILL to {$this->getName()}");
            return $this->process->kill($this->getPid(), SIGKILL);
        }

        return true;
    }

    /**
     * Attempt to exit the current process instance.
     *
     * @return int The status with which the process exited
     */
    public function exit(): int
    {
        return $this->process->exit(0);
    }

    /**
     * Bind callback on different signals
     *
     * @param int $signal The signal on which to execute the worker
     * @param callable $callable The callable to execute
     */
    public function on(int $signal, callable $callback): bool
    {
        return $this->process->signal($signal, $callback);
    }

    /**
     * Return the PID of the currently running process
     *
     * @throws \RuntimeException If the process is not running
     */
    public function getPid(): ?int
    {
        $pid = $this->process->pid;
        if ($pid === false) {
            $error = swoole_last_error();
            $errno = swoole_errno();
            throw new \RuntimeException("[{$errno}] {$error}");
        }

        return $pid;
    }

    private function log(string $severity, string $message, array $data = [])
    {
        if ($this->logger !== null) {
            $this->logger->log($severity, $message, $data);
        }
    }
}
