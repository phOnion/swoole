<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Tasks;

interface WorkerInterface
{
    public function run($payload);
}
