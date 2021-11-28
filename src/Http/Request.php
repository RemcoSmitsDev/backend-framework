<?php

namespace Framework\Http;

class Request
{
    private static $curlInfo;
    private static $response;

    private object $getData;
    private object $postData;

    public function __construct()
    {
        // set request method
        $this->method = $this->method();

        // set all request data to properties
        foreach ((array)$this->get() as $key => $value) {
            $this->{$key} = $value;
        }

        // set all request data to properties
        foreach ((array)$this->post() as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function get(string|int $find = null)
    {
        $getData = $this->getData ?? $this->getData = (object)clearInjections($_GET);

        return !is_null($find) ?
            (property_exists($getData, $find) ? $getData->{$find} : null) :
            $getData;
    }

    public function post(string|int $find = null)
    {
        if (!isset($this->postData)) {
            // get data from other request types
            $dataArray = json_decode(file_get_contents('php://input'), true) ?? [];
            // if postData is not define/null
            $this->postData = (object)clearInjections(((array)$dataArray) + $_POST);
        }

        return !is_null($find) ?
            (property_exists($this->postData, $find) ? $this->postData->{$find} : null) :
            $this->postData;
    }

    public function exists()
    {
        // get all function args
        $args = func_get_args();
        // strtoupper
        $requestType = strtoupper($args[0] ?? '');
        // verwijder eerste item van de args array
        array_shift($args);

        // check if requestType is valid
        if ($requestType !== 'GET' && $requestType !== 'POST') {
            throw new \Exception("Je kan alleen een `GET` en `POST` request validaren", 1);
        }

        // loop trough all func args
        foreach ($args as $key) {
            // check if key is an string
            if (!is_string($key)) {
                throw new \Exception("De key moet een string zijn", 1);
            }

            // check if key exists
            if ($requestType == 'GET' && !array_key_exists($key, $_GET) || $requestType == 'POST' && !array_key_exists($key, $_POST)) {
                return false;
            }
        }
        return true;
    }

    public static function send(string $requestType, string $requestURL, $requestData = null, array $headers = []): self
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
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, !function_exists('config') || !config()::DEVELOPMENT_MODE);

        // krijg response van request
        $response = curl_exec($curl);

        self::$curlInfo = curl_getinfo($curl);
        self::$response = $response;

        // sluit connectie
        curl_close($curl);

        return new self();
    }

    public function response(): object
    {
        return (object)['data' => self::$response, 'info' => (object)self::$curlInfo];
    }

    public function uri(): string
    {
        // krijg de huidige url zonden get waardes
        return rtrim(explode('?', self::url(), 2)[0], '/') ?: '/';
    }

    public function url(): string
    {
        // krijg de huidige url zonden get waardes
        return rtrim($_SERVER['REQUEST_URI'], '/') ?: '/';
    }

    public function headers(string $findHeader = null)
    {
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

        return $findHeader ? $headers[$findHeader] ?? null : $headers;
    }

    public function method()
    {
        // Take the method as found in $_SERVER
        $method = $_SERVER['REQUEST_METHOD'];

        // If it's a HEAD request override it to being GET and prevent any output, as per HTTP Specification
        // @url http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
        if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
            // start output buffer
            ob_start();
            // set method to get
            $method = 'GET';
        }

        // If it's a POST request, check for a method override header
        elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // get all headers
            $headers = $this->headers();
            // check if headers exists
            if (isset($headers['X-HTTP-Method-Override']) && in_array($headers['X-HTTP-Method-Override'], ['PUT', 'DELETE', 'PATCH'])) {
                $method = $headers['X-HTTP-Method-Override'];
            }
        }

        // return method in strtoupper
        return strtoupper($method);
    }
}
