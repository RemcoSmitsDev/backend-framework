<?php

namespace Framework\Parallel;

class Task
{
    private SocketConnection $connection;

    private string $token = 'serializedResponse::';

    public function __construct(private int $order, private $callback)
    {
    }

    public static function new(int $order, callable $callback): self
    {
        return new self($order, $callback);
    }

    private function execute(): mixed
    {
        // return response
        return ($this->callback)();
    }

    public function executeChild(SocketConnection $connection)
    {
        // call closure
        $response = $this->execute();

        // make valid response for sending with socket
        $response = is_string($response) ? $response : $this->token . serialize($response);

        // write to parent and close socket
        $connection->write($response)->close();
    }

    public function finishTask(array &$runningTasks): string
    {
        // get reponse from callback
        $response = '';

        // loop trough generator and get all ouputs
        foreach ($this->getConnection()->read() as $output) {
            $response .= $output;
        }

        // close connection to child
        $this->getConnection()->close();

        // check if response is serialized
        if (str_starts_with($response, $this->token)) {
            $response = unserialize(
                substr(
                    $response,
                    strlen($this->token)
                )
            );
        }

        // remove task from tasks 
        unset($runningTasks[$this->getOrder()]);

        // return response
        return $response;
    }

    public function runTask()
    {
        // get sockets
        [$socketToParent, $socketToChild] = SocketConnection::createSocketPair();

        // get process id
        $processId = pcntl_fork();

        // check if is in child task
        if ($processId === 0) {
            // close child
            $socketToChild->close();

            // call closure
            $this->executeChild($socketToParent);

            // exit
            exit;
        }

        // close socket connection
        $socketToParent->close();

        // set connection
        $this->setConnection($socketToChild);

        // set process id
        return $this->setProcessId($processId);
    }

    public function isFinished(): bool
    {
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
