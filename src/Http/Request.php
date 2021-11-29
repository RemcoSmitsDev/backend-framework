<?php

namespace Framework\Http;

class Request
{
    private $curlInfo;
    private $response;

    private object $getData;
    private object $postData;

    private object $requestData;

    public function __construct()
    {
        // set request method
        $this->method = $this->method();

        // set all request(get) data
        $this->getData = (object) clearInjections($_GET);

        // set all request(post) data
        $this->postData = (object) clearInjections(array_merge(
            json_decode(file_get_contents('php://input'), true) ?? [],
            $_POST
        ));

        // merge all request data
        $this->requestData = (object) array_merge(
            (array) $this->getData,
            (array) $this->postData
        );
    }

    /**
     * This function will return value of class property is exists
     * @return mixed
     */

    public function __get($name): mixed
    {
        return property_exists($this->requestData, $name) ? $this->requestData->{$name} : null;
    }

    /**
     * This function will return all get data or the value by find key
     * @param string|int $find
     * @return mixed
     */

    public function get(string|int $find = null): mixed
    {
        // check if find is null
        if (is_null($find)) {
            return $this->getData;
        }

        // return get data
        return property_exists($this->getData, $find) ? $this->getData->{$find} : null;
    }

    /**
     * This function will return all post data or the value by find key
     * @param string|int $find
     * @return mixed
     */

    public function post(string|int $find = null): mixed
    {
        // check if find is null
        if (is_null($find)) {
            return $this->postData;
        }

        // return get data based on find value
        return property_exists($this->postData, $find) ? $this->postData->{$find} : null;
    }

    /**
     * This function will check if all keys exists in the current request
     * @param string[] $requestKey
     * @return bool
     */

    public function exists(string ...$requestKey): bool
    {
        // loop trough all func args
        foreach ($requestKey as $key) {
            // check if key exists
            if (!property_exists($this->requestData, $key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * This function will send an server request to an url
     * @param string $requestType
     * @param string $requestURL
     * @param mixed $requestData
     * @param array $headers
     * @return self
     */

    public function send(string $requestType, string $requestURL, mixed $requestData = null, array $headers = []): self
    {
        // define curl
        $curl = curl_init();

        // maak request headers klaar
        curl_setopt_array($curl, [
            CURLOPT_URL => $requestURL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($requestType),
            CURLOPT_POSTFIELDS => is_array($requestData) ? json_encode($requestData) : $requestData,
            CURLOPT_HTTPHEADER => $headers
        ]);

        // fix bugs ssl expired
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, !IS_DEVELOPMENT_MODE);

        // krijg response van request
        $response = curl_exec($curl);

        $this->curlInfo = curl_getinfo($curl);
        $this->response = $response;

        // sluit connectie
        curl_close($curl);

        return $this;
    }

    /**
     * This function will send information about sended server request + data
     * @return object
     */

    public function response(): object
    {
        return (object) [
            'data' => $this->response,
            'info' => (object) $this->curlInfo
        ];
    }

    /**
     * This function will return current uri like: /route/to
     * @return string
     */

    public function uri(): string
    {
        // krijg de huidige url zonden get waardes
        return rtrim(explode('?', $this->url(), 2)[0], '/') ?: '/';
    }

    /**
     * This function will return current URL with params
     * @return string
     */

    public function url(): string
    {
        // krijg de huidige url zonden get waardes
        return rtrim($_SERVER['REQUEST_URI'], '/') ?: '/';
    }

    /**
     * This function will return all headers of one header based on the findHeader param
     * @param string $findHeader
     * @return array|string|null
     */

    public function headers(string $findHeader = null): array|string|null
    {
        // keep track of all headers
        $headers = [];

        // If getallheaders() is available, use that
        if (function_exists('getallheaders')) {
            // get all headers
            $headers = getallheaders();

            // getallheaders() can return false if something went wrong
            if ($headers !== false) {
                return $findHeader ? $headers[$findHeader] ?? null : $headers;
            }
        }

        // Method getallheaders() not available or went wrong: manually extract 'm
        foreach ($_SERVER as $name => $value) {
            if ((substr($name, 0, 5) == 'HTTP_') || ($name == 'CONTENT_TYPE') || ($name == 'CONTENT_LENGTH')) {
                $headers[str_replace([' ', 'Http'], ['-', 'HTTP'], ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        // return header(s) based on find header key
        return $findHeader ? (array_key_exists($findHeader, $headers) ? $headers[$findHeader] : null) : $headers;
    }

    /**
     * This function will return current request type
     * @return string
     */

    public function method(): string
    {
        // Take the method as found in $_SERVER
        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        // If it's a HEAD request override it to being GET and prevent any output, as per HTTP Specification
        // @url http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
        if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
            // start output buffer
            ob_start();
            // set method to get
            $method = 'GET';
        }

        // If it's a POST request, check for a method override header
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(strtoupper($this->headers('X-HTTP-Method-Override')), ['PUT', 'DELETE', 'PATCH'])) {
            // check if headers exists
            $method = $this->headers('X-HTTP-Method-Override');
        }

        // return method in strtoupper
        return strtoupper($method);
    }
}
