<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks\Manager;

use Onion\Extra\Swoole\Process\Manager as ProcessManager;
use Onion\Extra\Swoole\Tasks\Interfaces\ManagerInterface;
use Onion\Extra\Swoole\Tasks\Task;

class Process implements ManagerInterface
{
    /** @var ProcessManager */
    private $processManager;

    public function __construct(ProcessManager $processManager)
    {
        $this->processManager = $processManager;
    }

    /**
     * Push a task to a worker for processing
     */
    public function push(Task $task): bool
    {
        if (!$this->processManager->hasInboundChannel($task->getName())) {
            throw new \LogicException(
                "Unable to push task '{$task->getName()}', no channel"
            );
        }

        return $this->processManager->message($task->getName(), $task);
    }
}
