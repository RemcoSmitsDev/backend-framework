<?php

declare(strict_types=1);

namespace Framework;

use Exception;
use Framework\Container\Container;
use Framework\Debug\Debug;
use Framework\Event\DefaultEvents\ErrorEvent;
use Framework\Event\DefaultEvents\QueryEvent;
use Framework\Event\Event;
use Framework\Http\Api;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations,
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 *
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
final class App
{
    /**
     * @var bool
     */
    private bool $isStarted = false;

    /**
     * @var Container|null
     */
    private ?Container $container = null;

    /**
     * @static
     *
     * @var array<string, bool>
     */
    private array $raySettings = [
        'enabled'        => false,
        'enableAutoShow' => true,
    ];

    public function __construct()
    {
        $this->container = Container::getInstance();

        $this->container()->addSingleton($this);
    }

    /**
     * This function will start all needed functions.
     *
     * @throws Exception
     *
     * @return void
     */
    public function start(): void
    {
        // check if app is already started
        if ($this->isStarted) {
            throw new Exception('Application already started!');
        }

        // check if app is in development mode
        $this->checkAppState();

        // set app started
        $this->isStarted = true;

        // start output buffer
        ob_start();

        // This will register the needed actions to catch all errors/exceptions
        Debug::register();

        // register default events
        Event::listen([
            'database-query' => QueryEvent::class,
            'error'          => ErrorEvent::class,
        ]);

        // set timezone
        date_default_timezone_set('Europe/Amsterdam');
        setlocale(LC_ALL, 'nl_NL');

        // set Security headers
        $this->setSecurityHeaders();

        // set session settings
        $this->setSession();

        // kijk of een van de tokens niet bestaat generate dan een token
        // om later ajax request te kunnen valideren
        if (!isset($_COOKIE['requestToken'], $_SESSION['requestToken'])) {
            Api::generateRequestToken();
        }
    }

    /**
     * This function will set all needed security headers.
     *
     * @return void
     */
    private function setSecurityHeaders(): void
    {
        // block all iframes
        header('X-Frame-Options: deny');
        // set xxs header protection header
        header('X-XSS-Protection: 1; mode=block');
        // protect content type
        header('X-Content-Type-Options: nosniff');
        // enable content strict Security
        header('Strict-Transport-Security: max-age=31536000');
    }

    /**
     * This function will set all secure session headers and start session.
     *
     * @return void
     */
    private function setSession(): void
    {
        // voeg http only session.cookie toe als de app niet op development mode staat
        ini_set('session.cookie_httponly', (string) !IS_DEVELOPMENT_MODE);
        // voeg secure session.cookie toe als de app niet op development mode staat
        ini_set('session.cookie_secure', (string) !IS_DEVELOPMENT_MODE);
        // set session cookie duration time (2 days)
        session_set_cookie_params(3600 * 24 * 2);

        // start session als die nog niet bestaat.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * This function checks app state(local|live) based on host.
     *
     * @return void
     */
    private function checkAppState(): void
    {
        // define localhost ips to check if is development
        $whitelistLocalIps = [
            '127.0.0.1',
            '::1',
        ];

        // kijk of de server local is zet dan development aan
        define('IS_DEVELOPMENT_MODE', in_array(gethostbyname(request()->server('REMOTE_ADDR', '')), $whitelistLocalIps) || in_array(gethostbyname(gethostname()), $whitelistLocalIps));

        // define host om in de config class te kunnen gebruiken
        define('HTTP_HOST', request()->server('HTTP_HOST', ''));

        // server root for constant vars
        define('SERVER_ROOT', request()->server('DOCUMENT_ROOT', ''));
    }

    /**
     * Gets the singleton instance of the container.
     *
     * @return Container
     */
    public function container(): Container
    {
        return $this->container ??= Container::getInstance();
    }

    /**
     * Set singleton instances to the container.
     *
     * @param ...object $classes
     *
     * @return self
     */
    public function setInstance(object ...$classes): self
    {
        // loop trough all classes
        foreach ($classes as $class) {
            $this->container()->addSingleton($class);
        }

        // return self
        return $this;
    }

    /**
     * Get singleton instance from the container.
     *
     * @param object|string-class $class
     *
     * @return ?object
     */
    public function getInstance(object|string $class): ?object
    {
        return $this->container()->getSingleton(is_object($class) ? $class::class : $class);
    }

    /**
     * Enables ray application and allows you to show debug information.
     *
     * @return void
     */
    public function enableRay(bool $enableAutoShow = true): void
    {
        $this->raySettings = [
            'enabled'        => true,
            'enableAutoShow' => $enableAutoShow,
        ];
    }

    /**
     * Checks if ray is enabled to send information to the application.
     *
     * @return bool
     */
    public function rayIsEnabled(): bool
    {
        return $this->getRaySettings()['enabled'];
    }

    /**
     * This will return current ray settings.
     *
     * @return array
     */
    public function getRaySettings(): array
    {
        return $this->raySettings;
    }
}
