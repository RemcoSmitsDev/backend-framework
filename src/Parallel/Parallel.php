<?php

namespace Framework\Parallel;

use Opis\Closure\SerializableClosure;
use function Opis\Closure\{serialize as s, unserialize as u};

class Parallel
{
    private array $runningTasks = [];

    private array $queue = [];

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        if (!function_exists('pcntl_fork')) {
            throw new \Exception("Cannot create process forks: PCNTL is not supported on this system.");
        }
    }

    public function add(callable ...$callbacks)
    {
        // keep track of all file names
        $serializedCallbacks = [];

        // loop trough all callbacks
        foreach ($callbacks as $callback) {
            // serialize callback
            $serializedCallbacks[] = serialize(new SerializableClosure($callback));
        }

        // make arg ready
        $arg = base64_encode(serialize($serializedCallbacks));

        // execute parallel executor
        exec('php ForkTest.php ' . $arg, $output, $code);

        try {
            return unserialize($output[0]);
        } catch (\Throwable $th) {
            // dump reponse frome execution process
            echo $th->getMessage();

            // set conflict response code
            response()->code(409)->exit();
        }
    }

    public function run(...$callbacks): array
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

    protected function runQueue(array $queue)
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

    public function finishTask(Task $task): mixed
    {
        $response = $task->output();

        // remove task from tasks 
        unset($this->runningTasks[$task->getOrder()]);

        // return response
        return $response;
    }

    protected function isRunning(): bool
    {
        return count($this->runningTasks) > 0;
    }

    private function shiftTaskFromQueue(){
        // check if the queue is empty
        if (!count($this->queue)){
            return false;
        }
        // remove first item
        $firstTask = array_shift($this->queue);
        // add first item to the runningTasks
        $this->runningTasks[] = $firstTask->runTask();
    }
}
