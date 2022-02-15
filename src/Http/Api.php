<?php

namespace Framework\Http;

use ReflectionException;

class Api
{
    /**
     * @param string ...$whitelistedURIs
     *
     * @throws ReflectionException
     *
     * @return bool
     */
    public static function listen(string ...$whitelistedURIs): bool
    {
        // allow only request from same origin
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_HOST']}");
        // allow only get and post requests
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH');
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

        // kijkt of de url begint met ../api/
        if (!str_starts_with(request()->uri(), '/api/')) {
            return false;
        }

        // validate of de token die mee wordt gestuurt goed is
        if (!self::validateToken() && !in_array(request()->uri(), $whitelistedURIs)) {
            response()->code(401)->exit();
        }

        // verwijder `.php` van de string als die er is
        // verwijder ../ | .. om er voor te zorgen dat het niet mogelijk is om de Directory te veranderen
        $URL = str_replace(['../', '..', '.php'], '', request()->uri());

        // voeg `.php` toe aan het einde van de url
        $URL .= '.php';

        // file path to api
        $path = realpath($_SERVER['DOCUMENT_ROOT']."/../{$URL}");

        // kijkt of de url van ajax request bestaat in de api folder
        if (!file_exists($path)) {
            return false;
        }

        // require bestand zodat ajax een response terug kan krijgen
        require_once $path;

        exit(0);
    }

    /**
     * @return bool
     */
    public static function fromOwnServer(): bool
    {
        return str_starts_with(preg_replace("/http:\/\/|https:\/\/|\/.*\/$|\s+/", '', request()->headers('HTTP_REFERER', '')), explode('/', request()->headers('HTTP_HOST', '').request()->uri())[0]);
    }

    /**
     * @return bool
     */
    public static function fromAjax(): bool
    {
        return strtolower(request()->headers('X-REQUESTED-WITH', '')) === 'xmlhttprequest';
    }

    /**
     * @return bool
     */
    public static function validateToken(): bool
    {
        // kijk of authToken bestaat in de request headers
        if (!request()->headers('Requesttoken')) {
            return false;
        }

        // get privious session
        $previousSessionRequestToken = $_SESSION['_requestToken'] ?? randomString(20);

        // get session request token
        $sessionRequestToken = $_SESSION['requestToken'] ?? randomString(20);

        // krijg authToken van request
        // verwijder `bearer_` van token string
        $tokenFromRequest = str_replace(
            'bearer_',
            '',
            request()->headers('Requesttoken', randomString(20))
        );

        // unset previous request token
        unset($_SESSION['_requestToken']);

        // return of token gelijk is aan request header token
        return $tokenFromRequest === $sessionRequestToken || $previousSessionRequestToken === $tokenFromRequest;
    }

    /**
     * @return string
     */
    public static function generateRequestToken(): string
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
            setcookie('requestToken', $newToken, time() + 3600, '/');
        }

        // set cookie
        $_COOKIE['requestToken'] = $newToken;

        // new request token
        return $newToken;
    }
}
