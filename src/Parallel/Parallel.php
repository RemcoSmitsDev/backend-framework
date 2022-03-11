<?php

declare(strict_types=1);

namespace Framework\Parallel;

use Closure;
use Exception;
use Opis\Closure\SerializableClosure;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations,
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 *
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
class Parallel
{
    private array $runningTasks = [];

    private array $queue = [];

    /**
     * @throws Exception
     */
    public function __construct()
    {
        if (!function_exists('pcntl_fork')) {
            throw new Exception('Cannot create process forks: PCNTL is not supported on this system!');
        }
    }

    /**
     * @param callable ...$callbacks
     *
     * @return mixed|void
     */
    public function add(callable ...$callbacks)
    {
        // keep track of all file names
        $serializedCallbacks = [];

        // loop through all callbacks
        foreach ($callbacks as $callback) {
            // serialize callback
            $serializedCallbacks[] = serialize(new SerializableClosure(Closure::fromCallable($callback)));
        }

        // make arg ready
        $arg = base64_encode(serialize($serializedCallbacks));

        // execute parallel executor
        exec('php ForkTest.php '.$arg, $output, $code);

        try {
            return unserialize($output[0]);
        } catch (\Throwable $th) {
            // dump response from execution process
            echo $th->getMessage();

            // set conflict response code
            response()->code(409)->exit();
        }
    }

    /**
     * @param callable ...$callbacks
     *
     * @return array
     */
    public function run(callable ...$callbacks): array
    {
        // keep track of tasks
        $tasks = [];

        // loop through all callbacks
        foreach ($callbacks as $order => $callback) {
            // add new task
            $tasks[$order] = new Task($callback, $order);
        }

        // return array
        return $this->wait(...$tasks);
    }

    /**
     * @param Task ...$queue
     *
     * @return array
     */
    private function wait(Task ...$queue): array
    {
        // keep track of response
        $response = [];

        // run queue and add items to running tasks
        $this->runQueue($queue);

        // loop check if is finished
        while ($this->isRunning()) {
            // loop through all tasks
            foreach ($this->runningTasks as $task) {
                // check if task is not finished
                if (!$task->isFinished()) {
                    continue;
                }

                // add response
                $response[$task->getOrder()] = $this->finishTask($task);

                // shift item to from queue to running tasks
                $this->shiftTaskFromQueue();
            }

            // check if is running and put 1 micro sleep
            if ($this->isRunning()) {
                usleep(1_000);
            }
        }

        // return response
        return $response;
    }

    /**
     * @param array $queue
     *
     * @return void
     */
    protected function runQueue(array $queue): void
    {
        // set queue
        $this->queue = $queue;

        // loop trough que
        foreach ($this->queue as $task) {
            // change task with new information(connection, processId)
            $this->runningTasks[$task->getOrder()] = $task->runTask();

            // unset item from queue
            unset($this->queue[$task->getOrder()]);
        }
    }

    /**
     * @param Task $task
     *
     * @return mixed
     */
    public function finishTask(Task $task): mixed
    {
        $response = $task->output();

        // remove task from tasks
        unset($this->runningTasks[$task->getOrder()]);

        // return response
        return $response;
    }

    /**
     * @return bool
     */
    private function isRunning(): bool
    {
        return count($this->runningTasks) > 0;
    }

    /**
     * @return void
     */
    private function shiftTaskFromQueue(): void
    {
        // check if the queue is empty
        if (!count($this->queue)) {
            return;
        }
        // remove first item
        $firstTask = array_shift($this->queue);
        // add first item to the runningTasks
        $this->runningTasks[] = $firstTask->runTask();
    }
}
