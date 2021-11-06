<?php

namespace Framework\Content;

use Framework\Http\Api;

class Content
{
    public static string $template = '404';
    public static string $title = '';
    public static string $part = '';
    public static string $type = '';
    public static string $routeName = '404';

    public static $data = null;

    public static function head()
    {
        if (!file_exists($path = realpath($_SERVER['DOCUMENT_ROOT']."/../templates/head.php"))) {
            return false;
        }
        if (Api::fromAjax()) {
            return false;
        }
        $GLOBALS['slugInformation'] = self::$data;
        $slugInformation = self::$data;
        require_once($path);
    }

    public static function template(string $template, $data = null): self
    {
        self::$data = $data;
        self::$template = $template;
        return new self();
    }

    public static function part(string $part): ?string
    {
        self::$part = $part;
        $path = realpath($_SERVER['DOCUMENT_ROOT']."/../templates/{$part}.php");

        // kijk of een template part bestaat
        if (file_exists($path)) {
            return $path;
        }
    }

    public static function footer()
    {
        // krijg footer als die bestaat
        if (!file_exists($path = realpath($_SERVER['DOCUMENT_ROOT']."/../templates/footer.php"))) {
            return false;
        }
        if (Api::fromAjax()) {
            return false;
        }
        require_once($path);
    }

    public static function title(string $title): self
    {
        self::$title = $title;
        return new self();
    }

    public static function renderTemplate(string $template = ''): void
    {
        if (empty($template)) {
            $template = self::$template;
        }

        // kijk of de template bestaat
        if (file_exists(realpath($_SERVER['DOCUMENT_ROOT']."/../templates/{$template}.php"))) {
            // maak de data ook bruikbaar in de template
            $GLOBALS['slugInformation'] = self::$data;

            $slugInformation = self::$data;

            require(realpath($_SERVER['DOCUMENT_ROOT']."/../templates/{$template}.php"));
        }
    }

    public static function getTemplateName(): string
    {
        return self::$template;
    }

    public static function getTitle(): string
    {
        return self::$title;
    }

    public static function type(string $type): self
    {
        self::$type = ucfirst($type);
        return new self();
    }

    public static function listen(): void
    {
        // krijg template als die bestaat
        $type = self::$type;
        // require wrapper template
        require_once(realpath($_SERVER['DOCUMENT_ROOT']."/../templates/include{$type}Content.php"));
    }

    public static function name(string $routeName): self
    {
        self::$routeName = $routeName;
        return new self();
    }

    public static function getRouteName(): string
    {
        return self::$routeName;
    }
}
