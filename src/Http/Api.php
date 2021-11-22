<?php

namespace Framework\Http;

class Api
{
    public static function listen(): bool
    {
        // allow only request from same origin
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_HOST']}");
        // allow only get and post requests
        header('Access-Control-Allow-Methods: GET, POST');
        // allow responsTypes
        header('Content-Type: text/html; application/json; charset=utf-8');

        // kijkt of er een ajax request is gestuurt
        if (!self::fromAjax()) {
            return false;
        }

        // kijkt of er een ajax request is gestuurt van de eigen server is
        if (!self::fromOwnServer()) {
            response()->code(403);
            return false;
        }

        // als de server geen request mag krijgen dat stop die gelijk
        if (!config()::API_ALLOW_REQUESTS) {
            response()->code(403);
            return false;
        }

        // kijkt of de url begint met ../api/
        if (!str_starts_with(request()->uri(), '/api/')) {
            return false;
        }

        // validate of de token die mee wordt gestuurt goed is
        if (!self::validateToken()) {
            response()->code(401);
            return false;
        }

        // verwijder get waardes uit url van ajax request
        $URL = request()->uri();

        // verwijder `.php` van de string als die er is
        // verwijder ../ | .. om er voor te zorgen dat het niet mogelijk is om de Directory te veranderen
        $URL = str_replace(['../','..','.php'], '', $URL);

        // voeg `.php` toe aan het einde van de url
        $URL .= '.php';

        // file path to api
        $path = realpath($_SERVER['DOCUMENT_ROOT']."/../{$URL}");

        // kijkt of de url van ajax request bestaat in de api folder
        if (!file_exists($path)) {
            return false;
        }

        // require bestand zodat ajax een response terug kan krijgen
        require_once($path);

        exit(0);
    }

    public static function fromOwnServer(): bool
    {
        if (!isset($_SERVER['HTTP_REFERER'])) {
            return false;
        }

        if (!str_starts_with(preg_replace("/http:\/\/|https:\/\/|\/.*\/$|\s+/", "", $_SERVER['HTTP_REFERER']), explode("/", $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"])[0])) {
            return false;
        }

        return true;
    }

    public static function fromAjax(): bool
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        }
        return false;
    }

    private static function validateToken(): bool
    {
        // kijk of authToken bestaat in de request headers
        if (!isset(getallheaders()['Requesttoken'])) {
            return false;
        }

        // krijg authToken van request
        $tokenFromRequest = getallheaders()['Requesttoken'] ?? '';

        // verwijder `bearer_` van token string
        $tokenFromRequest = str_replace('bearer_', '', $tokenFromRequest);

        // return of token gelijk is aan request header token
        return $tokenFromRequest === $_SESSION['requestToken'] || (isset($_SESSION['_requestToken']) && $_SESSION['_requestToken'] === $tokenFromRequest);
    }

    public static function generateRequestToken(): self
    {
        // kijk of er al een request token bestaat voeg die dan toe als old `_` request token
        // voor als een request langzaam door de eerste check gaat
        if (isset($_SESSION['requestToken'])) {
            $_SESSION['_requestToken'] = $_SESSION['requestToken'];
        }

        // generate random string
        $newToken = randomString(50);

        // voeg token toe aan session om later tegebruiken om te valideren
        $_SESSION['requestToken'] = $newToken;

        // kijk of er al headers zijn verzonden
        if (!headers_sent()) {
            // voeg token toe aan session om later tegebruiken in js om vervolgens het request te valideren
            setcookie('requestToken', $newToken, time()+3600, '/');
        }

        // return self
        return new self();
    }
}
