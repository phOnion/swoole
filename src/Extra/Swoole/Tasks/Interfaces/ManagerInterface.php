<?php
declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks\Interfaces;

use Onion\Extra\Swoole\Tasks\Task;

interface ManagerInterface
{
    public function push(Task $task): bool;
}
