<?php

namespace Framework\Parallel;

use function Opis\Closure\{serialize as s, unserialize as u};

class Task
{
    private SocketConnection $connection;

    private string $token = '[[serialized::';

    protected string $output = '';
    private \Closure $callback;
    private int $processId;

    public function __construct(callable $callback, private int $order)
    {
        $this->callback = \Closure::fromCallable($callback);
    }

    private function execute(): string
    {
        // call tasks and get response
        $response = ($this->callback)();

        // check if response is a string
        if (is_string($response)) {
            return base64_encode($response);
        }

        // return response
        return base64_encode($this->token . serialize($response));
    }

    public function executeChild(SocketConnection $connection): void
    {
        // call closure
        $response = $this->execute();

        // make valid response for sending with socket
        $response = is_string($response) ? $response : serialize($response);

        // write to parent and close socket
        $connection->write($response)->close();
    }

    public function output()
    {
        // loop trough generator and get all outputs
        foreach ($this->getConnection()->read() as $output) {
            $this->output .= $output;
        }

        // close connection to child
        $this->getConnection()->close();

        // base64 decode output
        $output = base64_decode($this->output);

        // check if response is serialized
        if (str_starts_with($output, $this->token)) {
            // unserialize data from websocket data
            $output = unserialize(
                substr($output, strlen($this->token))
            );
        }

        // return response
        return $output;
    }

    public function runTask()
    {
        // get sockets
        [$socketToParent, $socketToChild] = SocketConnection::createSocketPair();

        // get process id
        $processId = pcntl_fork();

        // check if is in child task
        if ($processId === 0) {
            // run child task
            $this->runChildTask($socketToChild, $socketToParent);
        }

        // close socket connection
        $socketToParent->close();

        // set connection
        $this->setConnection($socketToChild);

        // set process id
        return $this->setProcessId($processId);
    }

    private function runChildTask(SocketConnection $socketToChild, SocketConnection $socketToParent)
    {
        // close child
        $socketToChild->close();

        // call closure
        $this->executeChild($socketToParent);

        // exit
        exit;
    }

    public function isFinished(): bool
    {
        $this->output .= $this->connection->read()->current();

        // check if status is equals to processId
        $status = pcntl_waitpid($this->getProcessId(), $status, WNOHANG | WUNTRACED);

        return $status === $this->getProcessId();
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function getConnection(): SocketConnection
    {
        return $this->connection;
    }

    public function setConnection(SocketConnection $connection): self
    {
        // set connection
        $this->connection = $connection;

        // return self
        return $this;
    }

    public function setProcessId(int $processId): self
    {
        // set processId
        $this->processId = $processId;

        // return self
        return $this;
    }

    public function getProcessId(): int
    {
        return $this->processId;
    }
}
