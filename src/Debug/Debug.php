<?php

declare(strict_types=1);

namespace Framework\Debug;

use Curl\CaseInsensitiveArray;
use ErrorException;
use Framework\Content\Content;
use Framework\Http\Api;

/**
 * Lightweight PHP Framework. Includes fast and secure Database QueryBuilder, Models with relations, 
 * Advanced Routing with dynamic routes(middleware, grouping, prefix, names).  
 *
 * @author     Remco Smits <djsmits12@gmail.com>
 * @copyright  2021 Remco Smits
 * @license    https://github.com/RemcoSmitsDev/backend-framework/blob/master/LICENSE
 * @link       https://github.com/RemcoSmitsDev/backend-framework/
 */
class Debug
{
    /**
     * Max distance before/after the error line.
     */
    const MAX_DISTANCE = 10;

    /**
     * @var bool
     */
    private static bool $renderd = false;

    /**
     * @var array
     */
    private static array $data = [
        'errors'   => [],
        'queries'  => [],
        'requests' => [],
        'dumps'    => [],
    ];

    /**
     * @return void
     */
    public static function register(): void
    {
        // set show errors base on development mode
        ini_set('display_errors', (string) IS_DEVELOPMENT_MODE);
        ini_set('display_startup_errors', (string) IS_DEVELOPMENT_MODE);

        // shows errors when debug mode is on
        if (!IS_DEVELOPMENT_MODE) {
            return;
        }

        // register shutdown function
        register_shutdown_function(fn () => self::render());

        // report all errors
        error_reporting(E_ALL);

        // catch all errors/exceptions and send event
        set_error_handler(function (int $level, string $message, string $file = '', int $line = 0) {
            throw new ErrorException($message, 0, $level, $file, $line);
        });

        // handle exception handler
        set_exception_handler(function ($exception) {
            // append error
            self::$data['errors'][] = [
                'data' => $exception,
                'type' => 'Exception',
            ];

            // render debug page
            self::render();
        });
    }

    /**
     * This method will append a information to the state.
     *
     * @param string $type
     * @param mixed  $data
     *
     * @return void
     */
    public static function add(string $type, mixed $data): void
    {
        // check if error is not formatted correctly
        if (!is_array($data) && $type === 'errors') {
            $_data = $data;
            $data = [];
            $data['type'] = 'Exception';
            $data['data'] = $_data;
        }

        // appen data base on the type
        self::$data[$type][] = $data;
    }

    /**
     * This method will render the debug screen if there where errors found.
     *
     * @return void
     */
    public static function render()
    {
        // stop when there where no errors found
        if (
            empty(self::$data['errors']) ||
            self::$renderd ||
            !IS_DEVELOPMENT_MODE ||
            !Api::fromOwnServer() ||
            str_contains(request()->headers('Accept', ''), 'json') ||
            Api::fromAjax()
        ) {
            return;
        }

        // set renderd
        self::$renderd = true;

        // catch all content
        ob_get_clean();

        // get code preview
        $codepreview = self::getCodePreview(
            self::$data['errors'][0]['data']->getFile(),
            self::$data['errors'][0]['data']->getLine()
        );

        // get data without(default fields)
        $data = arrayWithout(self::$data, 'errors', 'queries', 'requests');

        // show debug page
        app(new Content(realpath(__DIR__).'/views/', 'debugViewLayout'))
            ->template(
                'debugViewTemplate',
                array_merge(
                    [
                        'data' => $data,
                    ],
                    [
                        'codepreview' => $codepreview,
                        'errors'      => self::$data['errors'],
                        'queries'     => self::$data['queries'],
                        'requests'    => self::$data['requests'],
                    ]
                )
            )
            ->listen();

        exit;
    }

    /**
     * This method will code preview by a file + error line.
     *
     * @param string $path
     * @param int    $line
     *
     * @return void
     */
    public static function getCodePreview(string $path, int $line)
    {
        // open file
        $file = fopen($path, 'r');

        // check if can open file
        if (!$file) {
            // close file
            return fclose($file);
        }

        // keep track of lines information
        $lines = [
            'snippet'     => '',
            'lineNumbers' => [],
            'line'        => $line,
            'file'        => $path,
        ];

        // keep track of line number
        $lineNumber = 1;

        // loop through all lines
        while (($l = fgets($file)) !== false) {
            // when is in range of line(where the error was from)
            if ($lineNumber >= ($line - self::MAX_DISTANCE) && $lineNumber <= ($line + self::MAX_DISTANCE)) {
                // decode line
                $l = clearInjections($l);

                // get class name if is error line
                $errorClass = $line === $lineNumber ? 'bg-red-500/40 text-opacity-75' : '';

                // get url to the editor
                $url = self::getCodeEditorUrl($path, $lineNumber);

                // append to lines
                $lines['lineNumbers'][] = $lineNumber;
                $lines['snippet'] .= "<code-preview-line class=\"flex group leading-loose hover:bg-red-500/10 text-xs cursor-pointer {$errorClass}\" data-line=\"{$lineNumber}\" onclick=\"window.location.href='{$url}'\"><span class=\"pl-6\"><span>{$l}</span></span></code-preview-line>";
            }

            // check if is above the max distance
            if ($lineNumber > ($line + self::MAX_DISTANCE)) {
                break;
            }

            // increment lineNumber
            $lineNumber++;
        }

        // close file
        fclose($file);

        // return lines information
        return $lines;
    }

    /**
     * This method will format curl reqeust(for terminal).
     *
     * @param string                     $url
     * @param array|CaseInsensitiveArray $headers
     *
     * @return string
     */
    public static function formatCurlRequest(string $url, array|CaseInsensitiveArray $headers): string
    {
        // htmlspecialchars
        $url = clearInjections($url);

        // start curl format
        $curlFormat = "curl '{$url}' \<br>";

        // loop through all the headers
        foreach ($headers as $key => $value) {
            $curlFormat .= clearInjections("-H '{$key}: {$value}'").' \<br>';
        }

        // return formatted curl request
        return trim($curlFormat, ' \<br>').';';
    }

    /**
     * This method will get the url to the editor(file, line).
     *
     * @param string $path
     * @param int    $line
     *
     * @return string
     */
    public static function getCodeEditorUrl(string $path, int $line): string
    {
        // fix forward slashes
        $path = preg_quote($path);

        return "vscode://file/{$path}:{$line}";
    }

    /**
     * This method will choose a name to show in the debug trace
     * Based on the class/file name.
     *
     * @param string $class
     * @param string $file
     *
     * @return string
     */
    public static function chooseName(string $class, string $file): string
    {
        // explode class namespace
        $classParts = explode('\\', $class);

        // check if class is from file
        // then return class name(namespace)
        if (str_ends_with(str_replace('.php', '', $file), $classParts[count($classParts) - 1] ?? '')) {
            return $class;
        }
        // else return file(the file uses instance of the class)
        else {
            return $file;
        }
    }
}
