<?php

namespace Framework\Parallel;

class Task
{
    /**
     * @var SocketConnection
     */
    private SocketConnection $connection;

    /**
     * @var string
     */
    private string $token = '[[serialized::';

    /**
     * @var string
     */
    protected string $output = '';

    /**
     * @var \Closure
     */
    private \Closure $callback;

    /**
     * @var int
     */
    private int $processId;

    /**
     * @param callable $callback
     * @param int      $order
     */
    public function __construct(callable $callback, private int $order)
    {
        $this->callback = \Closure::fromCallable($callback);
    }

    /**
     * @return string
     */
    private function execute(): string
    {
        // call tasks and get response
        $response = ($this->callback)();

        // check if response is a string
        if (is_string($response)) {
            return base64_encode($response);
        }

        // return response
        return base64_encode($this->token.serialize($response));
    }

    /**
     * @param SocketConnection $connection
     */
    public function executeChild(SocketConnection $connection): void
    {
        // call closure
        $response = $this->execute();

        // make valid response for sending with socket
        $response = is_string($response) ? $response : serialize($response);

        // write to parent and close socket
        $connection->write($response)->close();
    }

    /**
     * @return mixed
     */
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

    /**
     * @return $this
     */
    public function runTask(): self
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

        // set connection and set process id
        return $this->setConnection($socketToChild)->setProcessId($processId);
    }

    /**
     * @param SocketConnection $socketToChild
     * @param SocketConnection $socketToParent
     *
     * @return void
     */
    private function runChildTask(SocketConnection $socketToChild, SocketConnection $socketToParent): void
    {
        // close child
        $socketToChild->close();

        // call closure
        $this->executeChild($socketToParent);

        // exit
        exit;
    }

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        $this->output .= $this->connection->read()->current();

        // check if status is equals to processId
        $status = pcntl_waitpid($this->getProcessId(), $status, WNOHANG | WUNTRACED);

        return $status === $this->getProcessId();
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * @return SocketConnection
     */
    public function getConnection(): SocketConnection
    {
        return $this->connection;
    }

    /**
     * @param SocketConnection $connection
     *
     * @return $this
     */
    public function setConnection(SocketConnection $connection): self
    {
        // set connection
        $this->connection = $connection;

        // return self
        return $this;
    }

    /**
     * @param int $processId
     *
     * @return $this
     */
    public function setProcessId(int $processId): self
    {
        // set processId
        $this->processId = $processId;

        // return self
        return $this;
    }

    /**
     * @return int
     */
    public function getProcessId(): int
    {
        return $this->processId;
    }
}
