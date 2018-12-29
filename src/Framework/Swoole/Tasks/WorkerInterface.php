<?php declare(strict_types=1);
namespace Onion\Framework\Swoole\Tasks;

interface WorkerInterface
{
    /**
     * The unit of work for the method, it will get invoked as soon as
     * there is data for the given worker
     */
    public function run($payload);
}
