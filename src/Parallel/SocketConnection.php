<?php

namespace Framework\Parallel;

use function Opis\Closure\{serialize as s, unserialize as u};

class SocketConnection
{
    private int $bufferSize = 1024;
    private float $timeout = 0.1;

    public function __construct(private \Socket $socket)
    {
        // set nonblock
        socket_set_nonblock($this->socket);
    }

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

    private function selectSocket(): false|int
    {
        $write = [$this->socket];
        $read = null;
        $except = null;

        return socket_select($read, $write, $except, $this->timeout);
    }

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

    public function read(): \Generator
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

    public function close(): self
    {
        // close socket connection
        socket_close($this->socket);

        // return self
        return $this;
    }
}
