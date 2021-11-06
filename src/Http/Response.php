<?php

namespace Framework\Http;

use Framework\Interfaces\Http\ResponseInterface;
use Framework\Chain;

class Response implements ResponseInterface
{
    private static string $responseData = '';
    private static int|string $responseCode = 200;
    private static bool $isJson = false;

    private static string|int $exitStatus = 0;
    private static bool $exit = false;

    private static Chain $chain;

    public function __construct()
    {
        self::$chain = new Chain($this, function () {
            self::handleResponse();
        });
    }

    /**
    * function json
    * formats responseData into json based in input data
    * @param array|string $responseData
    * @return $this   checks if this function is the last in the chain
    **/

    public static function json(array|object|string $responseData): self
    {
        // set response data
        self::$responseData = is_string($responseData) ? $responseData : json_encode($responseData);
        // update is json
        self::$isJson = true;
        // handle if is last method in the chain
        return self::$chain->chain();
    }

    /**
    * function text
    * sets reponse data
    * @param string $responseData
    * @return $this   checks if this function is the last in the chain
    **/

    public static function text(string $responseData): self
    {
        // set response data
        self::$responseData = $responseData;
        // update is json
        self::$isJson = false;
        // handle if is last method in the chain
        return self::$chain->chain();
    }

    /**
    * function code
    * send http response code to the client
    * @param int $responseCode = 200
    * @return $this   checks if this function is the last in the chain
    **/

    public static function code(int $responseCode = 200): self
    {
        // set response code
        self::$responseCode = $responseCode;
        // handle if is last method in the chain
        return self::$chain->chain();
    }

    /**
    * function headers
    * formats headers and send headers to the client
    * @param array $headers
    * @return $this   checks if this function is the last in the chain
    **/

    public static function headers(array $headers): self
    {
        // loop trough all headers
        foreach ($headers as $key => $value) {
            // split array into string with comma's
            $value = implode(',', (array)$value);

            // set header with value
            header("{$key}:{$value}");
        }
        // handle if is last method in the chain
        return self::$chain->chain();
    }

    /**
    * function exit
    * stops the page for rendering other data with responseCode
    * @param string|int $responseData
    * @return $this   checks if this function is the last in the chain
    **/

    public static function exit(string|int $status = 0)
    {
        // set exit to true with status value
        self::$exit = true;
        self::$exitStatus = $status;

        // handle if is last method in the chain
        return self::$chain->chain();
    }

    /**
    * function handleResponse
    * handles response to client on last function in the chain
    * @static
    * @return void
    **/

    public static function handleResponse()
    {
        // set response code
        http_response_code(self::$responseCode);
        // check if is json
        if (self::$isJson) {
            // set content header
            header('Content-Type: application/json; charset=UTF-8');
        } else {
            // set content headers
            header('Content-Type: text/html; charset=UTF-8');
        }
        // echo responseData
        echo self::$responseData;

        // check if exit function need to be set
        if (self::$exit) {
            exit(self::$exitStatus);
        }
    }
}
