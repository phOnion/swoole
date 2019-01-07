<?php declare(strict_types=1);
namespace Onion\Framework\Swoole\Server\Handlers;

use Onion\Framework\Swoole\Tasks\Task;
use Onion\Framework\Swoole\Tasks\WorkerInterface;
use Swoole\Server;

class TaskHandler
{
    /** @var WorkerInterface[] $workers */
    private $workers = [];

    /** @param WorkerInterface[] $workers */
    public function __construct(array $workers)
    {
        $this->workers = $workers;
    }

    public function __invoke(Server $server, int $task, int $source, $data)
    {
        /** @var Task $data */
        $name = $data->getName();

        if (!isset($this->workers[$name])) {
            throw new \UnexpectedValueException("No task worker registered for '{$name}");
        }

        try {
            $result = $this->workers[$name]->run($data->getPayload());
        } catch (\Exception $ex) {
            $result = $ex;
        }

        $server->finish($result);
    }
}
