<?php

namespace Framework;

use Framework\Content\Content;
use Framework\Http\Route\Route;
use Framework\Http\Api;

class App
{
    public function start()
    {
        // set timezone
        date_default_timezone_set('Europe/Amsterdam');
        setlocale(LC_ALL, 'nl_NL');

        // check if app is in development mode
        $this->checkAppState();

        // get error based on config option debug mode
        $this->catchErrors();

        // set Security headers
        $this->setSecurityHeaders();

        // set session settings
        $this->setSession();

        // require helper functions
        require_once(__DIR__.'/helperFunctions.php');

        // kijk of een van de tokens niet bestaat generate dan een token
        // om later ajax request te kunnen valideren
        if (!isset($_COOKIE['requestToken'],$_SESSION['requestToken'])) {
            Api::generateRequestToken();
        }
    }

    private function setSecurityHeaders(): void
    {
        // block all iframes
        header('X-Frame-Options:deny');
        // set xxs header protection header
        header('X-XSS-Protection: 1; mode=block');
        // protect content type
        header('X-Content-Type-Options: nosniff');
        // enable content strict Security
        header('Strict-Transport-Security: max-age=31536000');
    }

    private function setSession(): void
    {
        // voeg http only session.cookie toe als de app niet op development mode staat
        ini_set('session.cookie_httponly', !IS_DEVELOPMENT_MODE);
        // voeg secure session.cookie toe als de app niet op development mode staat
        ini_set('session.cookie_secure', !IS_DEVELOPMENT_MODE);
        // set session cookie duration time (2 days)
        session_set_cookie_params(3600 * 24 * 2);

        // start session als die nog niet bestaat.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function catchErrors(): void
    {
        // shows errors when debug mode is on
        if (IS_DEVELOPMENT_MODE) {
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

    private function checkAppState(): void
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

        // server root for constant vars
        define('SERVER_ROOT', $_SERVER['DOCUMENT_ROOT']);
    }
}
