<?php

namespace Framework\Parallel;

use Generator;

class SocketConnection
{
    /**
     * @var integer
     */
    protected int $timeoutSeconds;

    /**
     * @var integer
     */
    protected int $timeoutMicroseconds;

    /**
     * @var int
     */
    private int $bufferSize = 1024;

    /**
     * @var float
     */
    private float $timeout = 0.0001;

    /**
     * @param \Socket $socket
     */
    public function __construct(private \Socket $socket)
    {
        // set nonblock
        socket_set_nonblock($this->socket);

        // round number
        $this->timeoutSeconds = floor($this->timeout);

        // calc timeout microseconds
        $this->timeoutMicroseconds = ($this->timeout * 1_000_000) - ($this->timeoutSeconds * 1_000_000);
    }

    /**
     * @return SocketConnection[]
     */
    public static function createSocketPair(): array
    {
        // make socket
        socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $sockets);

        // set sockets(main process, child process)
        [$socketToParent, $socketToChild] = $sockets;

        // get sockets
        return [
            new self($socketToParent),
            new self($socketToChild)
        ];
    }

    /**
     * @return false|int
     */
    private function selectSocket(): false|int
    {
        $write = [$this->socket];
        $read = null;
        $except = null;

        return socket_select($read, $write, $except, $this->timeoutSeconds, $this->timeoutMicroseconds);
    }

    /**
     * @param string $payload
     * @return $this
     */
    public function write(string $payload): self
    {
        // set nonblock mode
        socket_set_nonblock($this->socket);

        while ($payload !== '') {
            // select socket
            $selectResult = $this->selectSocket();

            // check if there was something wrong when selecting the socket target
            if ($selectResult === false) {
                break;
            }

            // get payload length
            $length = mb_strlen($payload);

            // write socket data to child
            $amountOfBytesSent = socket_write($this->socket, $payload, $length);

            // check if all the payload data was sent
            if ($amountOfBytesSent === $length) {
                break;
            }

            // strip payload until payload is empty
            $payload = substr($payload, $amountOfBytesSent);
        }

        // return self
        return $this;
    }

    /**
     * @return Generator
     */
    public function read(): Generator
    {
        // set nonblock mode
        socket_set_nonblock($this->socket);

        while (true) {
            // select socket
            $selectResult = $this->selectSocket();

            // check if there was something wrong when selecting the socket target
            if ($selectResult === false || $selectResult <= 0) {
                break;
            }

            // read output from socket connection until all information was readed
            $output = socket_read($this->socket, $this->bufferSize);

            // check if all information was read
            if ($output === '') {
                break;
            }

            // yield output
            yield $output;
        }
    }

    /**
     * @return $this
     */
    public function close(): self
    {
        // close socket connection
        socket_close($this->socket);

        // return self
        return $this;
    }
}
