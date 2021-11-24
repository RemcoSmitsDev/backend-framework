<?php

namespace Framework\Http;

use Framework\Interfaces\Http\ResponseInterface;
use Framework\Chain\Chain;

class Response implements ResponseInterface
{
    private string $responseData = '';
    private int|string $responseCode = 200;
    private bool $isJson = false;

    private string|int $exitStatus = 0;
    private bool $exit = false;

    private Chain $chain;

    public function __construct()
    {
        $this->chain = new Chain($this, function () {
            $this->handleResponse();
        });
    }

    /**
     * function json
     * formats responseData into json based in input data
     * @param array|string $responseData
     * @return $this   checks if this function is the last in the chain
     **/

    public function json(array|object|string $responseData): self
    {
        // set response data
        $this->responseData = is_string($responseData) ? $responseData : json_encode($responseData);
        // update is json
        $this->isJson = true;
        // handle if is last method in the chain
        return $this->chain->chain();
    }

    /**
     * function text
     * sets reponse data
     * @param string $responseData
     * @return $this   checks if this function is the last in the chain
     **/

    public function text(string $responseData): self
    {
        // set response data
        $this->responseData = $responseData;
        // update is json
        $this->isJson = false;
        // handle if is last method in the chain
        return $this->chain->chain();
    }

    /**
     * function code
     * send http response code to the client
     * @param int $responseCode = 200
     * @return $this   checks if this function is the last in the chain
     **/

    public function code(int $responseCode = 200): self
    {
        // set response code
        $this->responseCode = $responseCode;
        // handle if is last method in the chain
        return $this->chain->chain();
    }

    /**
     * function headers
     * formats headers and send headers to the client
     * @param array $headers
     * @return $this   checks if this function is the last in the chain
     **/

    public function headers(array $headers): self
    {
        // loop trough all headers
        foreach ($headers as $key => $value) {
            // split array into string with comma's
            $value = implode(',', (array)$value);

            // set header with value
            header("{$key}:{$value}");
        }
        // handle if is last method in the chain
        return $this->chain->chain();
    }

    /**
     * function exit
     * stops the page for rendering other data with responseCode
     * @param string|int $responseData
     * @return $this   checks if this function is the last in the chain
     **/

    public function exit(string|int $status = 0)
    {
        // set exit to true with status value
        $this->exit = true;
        $this->exitStatus = $status;

        // handle if is last method in the chain
        return $this->chain->chain();
    }

    /**
     * function handleResponse
     * handles response to client on last function in the chain
     *
     * @return void
     **/

    public function handleResponse()
    {
        // check if the headers are already send
        if (!headers_sent()) {
            // set response code
            http_response_code($this->responseCode);
            // check if is json
            if ($this->isJson) {
                // set content header
                header('Content-Type: application/json; charset=UTF-8');
            } else {
                // set content headers
                header('Content-Type: text/html; charset=UTF-8');
            }
        }

        // echo responseData
        echo $this->responseData;

        // check if exit function need to be set
        if ($this->exit) {
            exit($this->exitStatus);
        }
    }
}
