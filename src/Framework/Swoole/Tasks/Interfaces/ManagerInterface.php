<?php
declare(strict_types=1);
namespace Onion\Framework\Swoole\Tasks\Interfaces;

use GuzzleHttp\Promise\PromiseInterface as Promise;
use Onion\Framework\Swoole\Tasks\Task;

interface ManagerInterface
{
    public function async(Task $task): Promise;
    public function sync(Task $task, int $timeout = 1): Promise;
    public function parallel(array $task, int $timeout = 1): Promise;
    public function all(array $tasks, ?int $timeout = 10000): Promise;
    public function race(array $tasks, ?int $timeout = 10000): Promise;
    public function schedule(Task $task, int $interval): Promise;
    public function delay(Task $task, int $interval): Promise;
}
