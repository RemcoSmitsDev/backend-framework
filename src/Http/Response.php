<?php

declare(strict_types=1);

namespace Framework\Http;

use Framework\Interfaces\Http\ResponseInterface;
use ReflectionException;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations, 
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).  
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
class Response implements ResponseInterface
{
    /**
     * Holds all response messages by response code.
     *
     * @var array
     */
    public array $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * Keeps track of response data.
     *
     * @var string
     */
    private string $responseData = '';

    /**
     * Keeps track of responseCode.
     *
     * @var int|string|null
     */
    private int|string|null $responseCode = null;

    /**
     * keeps track of is json.
     *
     * @var bool
     */
    private bool $isJson = false;

    /**
     * Keeps track of exit code/message.
     *
     * @var string|int
     */
    private string|int $exitMessage = 0;

    /**
     * Keep track if need to exit out.
     *
     * @var bool
     */
    private bool $exit = false;

    /**
     * Keeps track of content class.
     *
     * @var bool
     */
    private bool $content = false;

    /**
     * formats responseData into json based in input data.
     *
     * @param mixed $responseData
     *
     * @return self
     **/
    public function json(mixed $responseData): self
    {
        // set response data
        $this->responseData = is_string($responseData) ? $responseData : json_encode($responseData);
        // update is json
        $this->isJson = true;

        // return self
        return $this;
    }

    /**
     * sets reponse data.
     *
     * @param string $responseData
     *
     * @return self
     **/
    public function text(string $responseData): self
    {
        // set response data
        $this->responseData = $responseData;
        // update is json
        $this->isJson = false;

        // return self
        return $this;
    }

    /**
     * send http response code to the client.
     *
     * @param int $responseCode = 200
     *
     * @return self
     **/
    public function code(int $responseCode = 200): self
    {
        // set response code
        $this->responseCode = $responseCode;

        // return self
        return $this;
    }

    /**
     * formats headers and send headers to the client.
     *
     * @param array $headers
     *
     * @return self
     **/
    public function headers(array $headers): self
    {
        // loop trough all headers
        foreach ($headers as $key => $value) {
            // split array into string with comma's
            $value = implode(',', (array) $value);

            // set header with value
            header("{$key}:{$value}");
        }

        // return self
        return $this;
    }

    /**
     * stops the page for rendering other data with responseCode.
     *
     * @param string|int $status
     *
     * @return self
     */
    public function exit(string|int $status = 0): self
    {
        // set exit to true with status value
        $this->exit = true;
        $this->exitMessage = $status;

        // return self
        return $this;
    }

    /**
     * get view into response data.
     *
     * @param string $view
     * @param array  $data
     *
     * @return self
     */
    public function view(string $view, array $data = []): self
    {
        // get output buffer
        content()->template($view, $data);

        // set content use to true
        $this->content = true;

        // return self
        return $this;
    }

    /**
     * This function returns response message based on responseCode.
     *
     * @param int|null $responseCode
     *
     * @return string
     */
    public function getMessage(?int $responseCode = null): string
    {
        return $this->statusTexts[$responseCode ?: http_response_code()] ?? '';
    }

    /**
     * handles response to user when class closes.
     *
     * @throws ReflectionException
     */
    public function __destruct()
    {
        // check if the headers are already send
        if (!headers_sent()) {
            // check if there was an response code set
            if (isset($this->responseCode)) {
                // set response code
                header('HTTP/1.1 '.$this->responseCode.' '.($this->statusTexts[$this->responseCode] ?? ''));
            }

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

        // check if there
        if ($this->content) {
            content()->layout(false)->renderTemplate();
        }

        // check if exit function need to be set
        if ($this->exit) {
            exit($this->exitMessage);
        }
    }
}
