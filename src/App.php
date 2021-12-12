<?php

namespace Framework;

use Framework\Http\Api;
use ReflectionException;

class App
{
    /**
     * This function will start all needed functions
     * @return void
     */
    public static function start()
    {
        // start output buffer
        ob_start();

        // check if app is in development mode
        self::checkAppState();

        // register shutdown function
        register_shutdown_function(function () {
            // when HEAD request clear response
            if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
                ob_get_clean();
            }
            // check if there are internal server errors
            if (error_get_last() && error_get_last()['type'] === E_ERROR) {
                // check when to get buffer
                if (!IS_DEVELOPMENT_MODE) {
                    // get all diplayed errors from buffer
                    ob_get_clean();
                }
                // return error page
                //                response()->code(500)->view('responseView')->exit();
            }
        });

        // set timezone
        date_default_timezone_set('Europe/Amsterdam');
        setlocale(LC_ALL, 'nl_NL');

        // get error based on config option debug mode
        self::catchErrors();

        // set Security headers
        self::setSecurityHeaders();

        // set session settings
        self::setSession();

        // require helper functions
        require_once(__DIR__ . '/helperFunctions.php');

        // kijk of een van de tokens niet bestaat generate dan een token
        // om later ajax request te kunnen valideren
        if (!isset($_COOKIE['requestToken'], $_SESSION['requestToken'])) {
            Api::generateRequestToken();
        }
    }

    /**
     * This function will set all needed security headers
     * @return void
     */
    private static function setSecurityHeaders(): void
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

    /**
     * This function will set all secure session headers and start session
     * @return void
     */
    private static function setSession(): void
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

    /**
     * This function handles/enables error reporting
     * @return void
     */
    private static function catchErrors(): void
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

    /**
     * This function checks app state(local|live) based on host
     * @return void
     */
    private static function checkAppState(): void
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

    /**
     * This function will store an instance of all classes
     * @param object[] $classes
     * @return self
     * @throws ReflectionException
     */
    public function instance(object ...$classes): self
    {
        // loop trough all classes
        foreach ($classes as $class) {
            // set class
            $this->{lcfirst(getClassName($class))} = $class;
        }

        // return self
        return $this;
    }
}
