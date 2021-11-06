<?php

namespace Framework\Http;

class Request
{
    private static $curlInfo;
    private static $response;

    private object $getData;
    private object $postData;

    public function get()
    {
        return $this->getData ?? $this->getData = (object)clearInjections($_GET);
    }

    public function post()
    {
        if (isset($this->postData)) {
            return $this->postData;
        }
        // get data from other request types
        $dataArray = json_decode(file_get_contents('php://input')) ?? [];
        // if postData is not define/null
        $this->postData = (object)clearInjections(((array)$dataArray) + $_POST);

        return $this->postData;
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

    public static function applyForwardHeaders(): array
    {
        // maak headers klaar
        // default: leeg
        $headers = [
          'Forwarded' => '',
          'X-Forwarded-Fo' => ''
        ];

        $userIP = auth()->getUserIP();

        // kijk of forward header bestaat voeg dan user ip toe
        // anders maak header aan
        if (isset(getallheaders()['Forwarded'])) {
            $headers['Forwarded'] = 'Forwarded: ' . getallheaders()['Forwarded'] . ', for="' . $userIP . '"';
        } else {
            $headers['Forwarded'] = 'Forwarded: for="' . $userIP . '"';
        }

        // kijk of the X-Forwarded-Fo header bestaat
        if (isset(getallheaders()['X-Forwarded-Fo'])) {
            $headers['X-Forwarded-Fo'] = 'X-Forwarded-Fo: ' . getallheaders()['X-Forwarded-Fo'] . ', ' . $userIP;
        } else {
            $headers['X-Forwarded-Fo'] = 'X-Forwarded-Fo: ' . $userIP;
        }

        return $headers;
    }


    public static function match(string $requestURL): bool
    {
        // verwijder laatste slash van url als hij dan leeg is maak er dan alleen een slash van
        $requestURL = rtrim($requestURL, '/') ?: '/';

        // kijk of de url gelijk is aan een ingestelde route
        // als er regex in de url zit dan wordt er gekeken of hiermee een match is
        if (self::URL() === $requestURL || preg_match('/^'.str_replace('/', '\/', $requestURL).'$/', self::URL())) {
            return true;
        }
        return false;
    }

    public static function send(string $requestType, string $requestURL, $requestData = null, array $headers = []): self
    {
        $requestTypse = ['GET','POST','PUT','DELETE'];

        // maak request type uppercase
        $requestType = strtoupper($requestType);

        // kijk of request type in toegestaande request type array staat.
        if (!in_array($requestType, $requestTypse)) {
            throw new \Exception("Geen geldig request type: {$requestType}", 1);
        }

        // define curl
        $curl = curl_init();

        // maak request headers klaar
        curl_setopt_array($curl, [
          CURLOPT_URL => $requestURL,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_CUSTOMREQUEST => $requestType,
          CURLOPT_POSTFIELDS => is_array($requestData) ? json_encode($requestData) : $requestData,
          CURLOPT_HTTPHEADER => $headers
        ]);

        // fix bugs ssl expired
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, !config()::DEVELOPMENT_MODE);

        // krijg response van request
        $response = curl_exec($curl);

        self::$curlInfo = curl_getinfo($curl);
        self::$response = $response;

        // sluit connectie
        curl_close($curl);

        return new self();
    }

    public static function response(): object
    {
        return (object)['data' => self::$response,'info' => (object)self::$curlInfo];
    }

    public static function URL(): string
    {
        // krijg de huidige url zonden get waardes
        return clearInjections(rtrim(explode('?', $_SERVER['REQUEST_URI'], 2)[0], '/') ?: '/');
    }

    public static function fullURL(): string
    {
        // krijg de huidige url zonden get waardes
        return clearInjections(rtrim($_SERVER['REQUEST_URI'], '/') ?: '/');
    }

    public static function method()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public static function URLInformation(string $URL = ''): array
    {
        // explode huidige url naar een array
        if (!empty($URL)) {
            return explode('/', rtrim($URL, '/'));
        }
        return explode('/', self::URL());
    }
}
