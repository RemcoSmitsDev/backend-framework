<?php

declare(strict_types=1);

namespace Framework\Cache;

use Closure;
use DateTime;
use Exception;
use ReflectionException;

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
class Cache
{
    /**
     * This will create the cache dir.
     *
     * @param string $cacheFolderPath
     */
    public function __construct(
        private string $cacheFolderPath = SERVER_ROOT.'/../cache/'
    ) {
        // add last slash
        $this->cacheFolderPath = rtrim($this->cacheFolderPath, '/').'/';

        // check if cache folder exists
        if (!file_exists($this->cacheFolderPath)) {
            mkdir($this->cacheFolderPath);
        }
    }

    /**
     * @param string $identifier
     * @param int    $lifeTime
     * @param string $type       (public | private)
     *
     * @throws ReflectionException
     *
     * @return self
     */
    public function http(string $identifier, int $lifeTime = 3600, string $type = 'public'): self
    {
        // get debug trace
        $debugTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        // check if request method is GET
        // check if there is an file found where the method was called from
        if (request()->method() !== 'GET' || !isset($debugTrace[0]['file'])) {
            return $this;
        }

        // get file path
        $file = $debugTrace[0]['file'];

        // get last modified based on if there is an active cache
        $lastModified = request()->headers('If-Modified-Since') ? (new DateTime(request()->headers('If-Modified-Since', 'now')))->getTimestamp() : (int) filemtime($file);

        // check if file is not modified between new last modified date
        if ((int) filemtime($file) > $lastModified) {
            // set last modified to file modified date
            $lastModified = (int) filemtime($file);
        }

        // check if there is an current http cache make sure the max-age us incrementing
        // based on the lifetime and last modified date
        [$newLifeTime, $lastModified] = $this->holdCurrentHttpCache($lifeTime, $lastModified);

        // make etag to match for specific content
        $etag = '"'.md5(clearInjections($identifier.$file.$lastModified)).'"';

        // add reponse headers
        response()->headers([
            // Set Cache-Control header
            'Cache-Control' => $type.', max-age='.max($newLifeTime, 0).', must-revalidate',
            // set last Modified
            'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified),
            // set expire date
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified + $lifeTime),
            // Set ETag header
            'ETag' => $etag,
        ]);

        // Check whether browser had sent a HTTP_IF_NONE_MATCH request header
        if (request()->headers('If-None-Match') === $etag) {
            // So send a 304 Not Modified response header and exit
            response()->code(304)->exit();
        }

        // return self
        return $this;
    }

    /**
     * This function check if there is an current http-cache and decrement max-age
     * When the max-age = 0 then the http-cache will be removed to force renew cache modified at + lifetime.
     *
     * @param int $lifeTime
     * @param int $lastModified
     *
     * @throws ReflectionException
     * @throws Exception
     *
     * @return array
     */
    private function holdCurrentHttpCache(int $lifeTime, int $lastModified): array
    {
        // check if there exist and cache
        if (!request()->headers('If-Modified-Since')) {
            // return null as lifeTime
            return [
                $lifeTime,
                $lastModified,
            ];
        }

        // get last modified timestamp(int)
        $lastModTimestamp = (new DateTime(request()->headers('If-Modified-Since')))->getTimestamp();
        // get current timestamp(int)
        $nowTimestamp = (new DateTime())->getTimestamp();

        // calc rest lifetime
        $lifeTime = $lifeTime - ($nowTimestamp - $lastModTimestamp);

        // check if the lifeTime is passed
        if ($lifeTime <= 0) {
            // return response headers
            response()->headers([
                // disable cache
                'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
                // set old expire date
                'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
            ]);

            // set last modified to now
            $lastModified = (new DateTime())->getTimestamp();
        }

        // return new information for cache
        return [
            $lifeTime,
            $lastModified,
        ];
    }

    /**
     * cache/get cached data.
     *
     * @param string  $identifier (unique identifier)
     * @param Closure $closure
     * @param int     $lifeTime   = 3600
     *
     * @return mixed
     **/
    public function data(string $identifier, Closure $closure, int $lifeTime = 3600): mixed
    {
        // krijg informatie terug van cache
        $cacheInfo = $this->getCacheItemInfo($identifier);

        // check if cache file was found
        if ($cacheInfo->fileFound) {
            // stuur file content terug in oude staat
            return $cacheInfo->data;
        }

        // krijg return data van closure
        $data = $closure();

        // voeg json data toe
        file_put_contents($this->cacheFolderPath.$cacheInfo->fileName, $lifeTime.';'.json_encode($data));

        // return data
        return $data;
    }

    /**
     * get single cache file if exists.
     *
     * @param string $identifier (unique identifier)
     *
     * @return bool|array
     **/
    public function get(string $identifier): bool|array
    {
        // krijg cache infor bij identifier
        $cacheInfo = $this->getCacheItemInfo($identifier);

        // return data als er een bestand is gevonden
        return $cacheInfo->fileFound ? $cacheInfo->data : false;
    }

    /**
     * removes cache file.
     *
     * @param string $identifier (unique identifier)
     * @param bool   $isFlushed
     *
     * @return void
     */
    public function remove(string $identifier, bool $isFlushed = false): void
    {
        // krijg cache infor bij identifier
        $cacheInfo = $this->getCacheItemInfo($identifier);

        // kijk of er een cache bestand is gevonden
        if ($cacheInfo->fileFound) {
            // verwijder cache file van cache
            unlink($this->cacheFolderPath.$cacheInfo->fileName);
        }

        // update cacheConfig and removed wrong cache files
        if (!$isFlushed) {
            $this->flushEndOfLiveTimeCacheItems();
        }
    }

    /**
     * gets inforamtion cache information by identifier.
     *
     * @param string $identifier (unique identifier)
     *
     * @return object
     **/
    private function getCacheItemInfo(string $identifier): object
    {
        // generate hash for identifier
        $identifier = $this->generateFileName($identifier);

        // default returndata template
        $returnData = (object) [
            'data'         => null,
            'lastModified' => null,
            'fileName'     => $identifier.'.json',
            'identifier'   => $identifier,
            'fileFound'    => true,
        ];

        // check if file exists
        if (!file_exists($this->cacheFolderPath.$returnData->fileName)) {
            // update fileFound prop
            $returnData->fileFound = false;
            // return data
            return $returnData;
        }

        // check if cache item is expired
        if (!($cacheItemData = $this->checkCacheItemLifeTime($returnData->fileName))) {
            // set no file found
            $returnData->fileFound = false;
            // krijg return data van closure
            return $returnData;
        }

        // set cache data to return data object
        $returnData->data = json_decode((string) $cacheItemData);

        // return data
        return $returnData;
    }

    /**
     * This function will check if the lifetime of an specific cache item is not expired.
     *
     * @param string $fileName
     *
     * @return bool|string|null
     */
    private function checkCacheItemLifeTime(string $fileName): bool|string|null
    {
        // check if cache file exists
        if (!file_exists($this->cacheFolderPath.$fileName)) {
            return false;
        }

        // get cache item
        $cacheItemData = explode(';', file_get_contents($this->cacheFolderPath.$fileName) ?: '', 2);

        // get cache file last modified date
        $lastModified = filemtime($this->cacheFolderPath.$fileName);

        // get lifetime from cached data
        $lifeTime = intval($cacheItemData[0] ?? 0);

        //  kijk of lifetime is verlopen
        if ($lastModified + $lifeTime <= time()) {
            // verwijder cache file
            unlink($this->cacheFolderPath.$fileName);
            // krijg return data van closure
            return false;
        }

        // return true not expired
        return $cacheItemData[1] ?? null;
    }

    /**
     * checkes if cacheFiles are still valid and cacheConfig is up to date.
     *
     * @return string
     **/
    public function flushEndOfLiveTimeCacheItems(): string
    {
        // scan through all cache files and check if lifetime passed
        $this->scanCacheFolder(function ($file) {
            // remove cache item if the lifetime is expired
            $this->checkCacheItemLifeTime($file);
        });

        // return last updated date
        return (new \DateTime())->format('Y-m-d H:i:s');
    }

    /**
     * makes hashed string to make filenames more random/secure.
     *
     * @param string $identifier
     *
     * @return string
     **/
    private function generateFileName(string $identifier): string
    {
        return md5($identifier);
    }

    /**
     * This method will delete all cache files.
     *
     * @return self
     */
    public function flush(): self
    {
        // loop through files and remove file
        $this->scanCacheFolder(function ($file) {
            // delete cache file
            unlink($this->cacheFolderPath.$file);
        });

        // return self
        return $this;
    }

    /**
     * This method will loop trough all cache files and calls the action that is needed.
     *
     * @param callable $callback
     *
     * @return void
     */
    private function scanCacheFolder(callable $callback)
    {
        // check if cache location exists
        if (!file_exists($this->cacheFolderPath)) {
            return;
        }

        // make closure from callable
        $callback = Closure::fromCallable($callback);

        // wrong files to filter out
        $wrongFiles = ['..', '.'];

        // loop trough all files and delete them
        foreach (scandir($this->cacheFolderPath) ?: [] as $file) {
            // let only valid files pass
            if (in_array($file, $wrongFiles)) {
                continue;
            }

            // execute action
            $callback($file);
        }
    }
}
