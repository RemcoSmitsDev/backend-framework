<?php

namespace Framework\Parallel;

class Parallel
{
    private array $runningTasks = [];

    private array $queue = [];

    public function add(callable ...$callbacks): array
    {
        // keep track of tasks
        $tasks = [];

        // loop trough all callbacks
        foreach ($callbacks as $order => $callback) {
            // add new task
            $tasks[$order] = Task::new($order, $callback);
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

            // loop trough all tasks
            foreach ($this->runningTasks as $task) {
                // check if task is not finished
                if (!$task->isFinished()) {
                    continue;
                }

                // add response
                $response[$task->getOrder()] = $task->finishTask($this->runningTasks);

                // check if there is an item in the queue
                if (count($this->queue)) {
                    // remove first item
                    $firstTask = array_shift($this->queue);
                    // add first item to the runningTasks
                    $this->runningTasks[] = $firstTask->runTask();
                }
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

    protected function isRunning(): bool
    {
        return count($this->runningTasks) > 0;
    }
}
