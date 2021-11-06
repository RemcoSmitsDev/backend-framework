<?php

namespace Framework\Http;

use Framework\Interfaces\Http\ResponseInterface;

class Response implements ResponseInterface
{
    private static string $responseData = '';
    private static int|string $responseCode = 200;
    private static string|int $exitStatus = 0;
    private static bool $exit = false;

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
        // handle if is last method in the chain
        return new self();
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
        // handle if is last method in the chain
        return new self();
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
        return new self();
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
        return new self();
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
        return new self();
    }

    /**
    * function __destruct
    * handles response to client on last function in the chain
    * @return void
    **/

    public function __destruct()
    {
        // set response code
        http_response_code(self::$responseCode);
        // echo responseData
        echo self::$responseData;

        // set temp data
        $exit = self::$exit;
        $exitStatus = self::$exitStatus;

        // Clear data to default properties
        self::$responseData = '';
        self::$responseCode = 200;

        self::$exit = false;
        self::$exitStatus = 0;

        // check if exit function need to be set
        if ($exit) {
            exit($exitStatus);
        }
    }
}
