<?php

namespace Framework\Parallel;

class SocketConnection
{
    public function __construct(private \Socket $socket)
    {
        // set noblock
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

    private function selectSocket(): bool
    {
        // options/settings for selecting socket
        $read = [$this->socket];
        $write = null;
        $except = null;

        // select socket
        $selectResult = socket_select($read, $write, $except, 0.1);

        // check if there was an socket found
        return $selectResult === false ? false : true;
    }

    public function write(string $payload): self
    {
        while ($payload !== '') {
            // check if there was an socket found
            if (!$this->selectSocket($this->socket)) {
                break;
            }

            // get length of payload
            $length = strlen($payload);

            // write socket
            $amountOfBytesSent = socket_write($this->socket, $payload, $length);

            // check if there was an valid response
            if ($amountOfBytesSent === false || $amountOfBytesSent === $length) {
                break;
            }

            // get correct payload based on amout of strlen payload
            $payload = substr($payload, $amountOfBytesSent);
        }

        // return self
        return $this;
    }

    public function read(): \Generator
    {
        socket_set_nonblock($this->socket);

        while (true) {
            // check if there was an socket found
            if (!$this->selectSocket($this->socket)) {
                break;
            }

            // read socket
            $outputFromSocket = socket_read($this->socket, 1024);

            // check if there was and correct output
            if ($outputFromSocket === false || $outputFromSocket === '') {
                break;
            }

            // return without stopping executiontime
            yield $outputFromSocket;
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
