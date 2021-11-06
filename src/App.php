<?php

namespace Framework;

use Framework\Content\Content;
use Framework\Http\route\Route;
use Framework\Http\Api;

class App
{
    public function start(Api $api, Content $content)
    {
        // set timezone
        date_default_timezone_set('Europe/Amsterdam');
        setlocale(LC_ALL, 'nl_NL');

        // api prefix
        define('API_PREFIX', '/api');
        // server root for constant vars
        define('SERVER_ROOT', $_SERVER['DOCUMENT_ROOT']);

        // check if app is [local|state(dev)|live]
        $this->checkAppState();

        // get error based on config option debug mode
        $this->catchErrors();

        // set Security headers
        $this->setSecurityHeaders()->setSession();

        // include all routes
        require_once(realpath('../routes.php'));

        // start listen for jquery ajax request
        // api route: /api/PATH_TO_FILE_INSIDE_API_FILE
        // then requires the correct file if request is valid
        $api->listen();

        // init all routes
        route()->init();

        // als er geen url match is dan wordt de 404 pagina weergegeven
        $content->listen();

        // kijk of een van de tokens niet bestaat generate dan een token
        // om later ajax request te kunnen valideren
        if (!isset($_COOKIE['requestToken'],$_SESSION['requestToken'])) {
            $api->generateRequestToken();
        }
    }

    private function setSecurityHeaders(): self
    {
        // block all iframes
        header('X-Frame-Options:deny');
        // set xxs header protection header
        header('X-XSS-Protection: 1; mode=block');
        // protect content type
        header('X-Content-Type-Options: nosniff');
        // enable content strict Security
        header('Strict-Transport-Security: max-age=31536000');

        return $this;
    }

    private function setSession(): self
    {
        // voeg http only session.cookie toe als de app niet op development mode staat
        ini_set('session.cookie_httponly', !\Config::DEVELOPMENT_MODE);
        // voeg secure session.cookie toe als de app niet op development mode staat
        ini_set('session.cookie_secure', !\Config::DEVELOPMENT_MODE);
        // set session cookie duration time (2 days)
        session_set_cookie_params(3600 * 24 * 2);

        // start session als die nog niet bestaat.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $this;
    }

    private function catchErrors()
    {
        // shows errors when debug mode is on
        if (\Config::DEBUG_MODE) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            // vang alle errors en echo alleen een empty string
            set_error_handler(function () {
                echo '';
            });
            set_exception_handler(function () {
                echo '';
            });
        }
    }

    private function checkAppState(): self
    {
        // define localhost ips to check if is development
        $whitelistLocalIps = [
          '127.0.0.1',
          '::1'
        ];

        // kijk of de server local is zet dan development aan
        define('IS_DEVELOPMENT_MODE', in_array(getHostByName(getHostName()), $whitelistLocalIps));

        // define host om in de config class te kunnen gebruiken
        define('HTTP_HOST', $_SERVER['HTTP_HOST']);

        return $this;
    }
}
