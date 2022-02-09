<?php

namespace Framework;

use Framework\Event\DefaultEvents\QueryEvent;
use Framework\Event\DefaultEvents\ErrorEvent;
use Framework\Debug\Debug;
use Framework\Event\Event;
use ReflectionException;
use Framework\Http\Api;
use ErrorException;

final class App
{
    /**
     * @var boolean
     */
    private static bool $isStarted = false;

    /**
     * @static
     * @var array $raySettings
     */
    private static array $raySettings = [
        'enabled' => false,
        'enableAutoShow' => true
    ];

    /**
     * @var array
     */
    private static $properties = [];

    /**
     * This function will start all needed functions
     * @return void
     */
    public static function start(): void
    {
        // check if app is already started
        if (self::$isStarted) {
            return;
        }

        // cache output
        ob_start();

        // register shutdown
        register_shutdown_function(function () {
            // check if is not development mode
            if (!IS_DEVELOPMENT_MODE || !Api::fromOwnServer()) return;

            // when comes from api | or wants json back
            if (str_contains(request()->headers('Content-Type', ''), 'application/json') || Api::fromAjax()) return;

            // render debug screen
            Debug::render();
        });

        // set app started
        self::$isStarted = true;

        // check if app is in development mode
        self::checkAppState();

        // register default events
        Event::listen([
            'database-query' => QueryEvent::class,
            'error' => ErrorEvent::class
        ]);

        // get error based on config option debug mode
        self::catchErrors();

        // set timezone
        date_default_timezone_set('Europe/Amsterdam');
        setlocale(LC_ALL, 'nl_NL');

        // set Security headers
        self::setSecurityHeaders();

        // set session settings
        self::setSession();

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
        // set show errors base on development mode
        ini_set('display_errors', IS_DEVELOPMENT_MODE);
        ini_set('display_startup_errors', IS_DEVELOPMENT_MODE);

        // shows errors when debug mode is on
        if (!IS_DEVELOPMENT_MODE) {
            return;
        }

        // report all errors
        error_reporting(E_ALL);

        // catch all errors/exceptions and send event
        set_error_handler(function (
            int $level,
            string $message,
            string $file = '',
            int $line = 0
        ) {
            // make Error exception
            throw new ErrorException($message, 0, $level, $file, $line);
        });
        set_exception_handler(fn ($exception) => Event::notify('error', ['data' => $exception, 'type' => 'Exception']));
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
        define('IS_DEVELOPMENT_MODE', in_array(gethostbyname(request()->server('REMOTE_ADDR', '')), $whitelistLocalIps) || in_array(getHostByName(getHostName()), $whitelistLocalIps));

        // define host om in de config class te kunnen gebruiken
        define('HTTP_HOST', request()->server('HTTP_HOST', ''));

        // server root for constant vars
        define('SERVER_ROOT', request()->server('DOCUMENT_ROOT', ''));
    }

    /**
     * This function will store an instance of all classes
     * @param ...object $classes
     * @return self
     */
    public static function setInstance(object ...$classes): self
    {
        // loop trough all classes
        foreach ($classes as $class) {
            // set class
            self::$properties[lcfirst(getClassName($class))] = $class;
        }

        // return self
        return new self;
    }

    /**
     * This method will get the container property instnace
     *
     * @param  object|string $class
     * @return object|null
     */
    public static function getInstance(object|string $class): object|null
    {
        return self::$properties[is_object($class) ? lcfirst(getClassName($class)) : $class] ?? null;
    }

    /**
     * This function will enable ray
     * @return void
     */
    public static function enableRay(bool $enableAutoShow = true): void
    {
        self::$raySettings = [
            'enabled' => true,
            'enableAutoShow' => $enableAutoShow
        ];
    }

    /**
     * @return bool
     */
    public static function rayIsEnabled(): bool
    {
        return self::getRaySettings()['enabled'];
    }

    /**
     * @return array This will return current ray settings
     */
    public static function getRaySettings(): array
    {
        return self::$raySettings;
    }
}
