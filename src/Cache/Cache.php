<?php

namespace Framework\Cache;

use DateTime;

class Cache
{
    /**
     * @var string $cacheConfigFilePath
     **/
    private string $cacheFolderPath = '';

    /**
     * @var string $cacheConfigFilePath
     */
    private string $cacheConfigFilePath = '';

    public function __construct()
    {
        $this->cacheFolderPath = SERVER_ROOT . '/../cache/';
        $this->cacheConfigFilePath = $this->cacheFolderPath . '__cacheConfig.json';
    }

    /**
     * function file
     * @param string $identifier
     * @param int $lifeTime
     * @param string $type (public | private)
     * @return self
     **/

    public function http(string $identifier, int $lifeTime = 3600, string $type = 'public'): self
    {
        // get debug trace
        $debugTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        // check if request method is GET
        // check if there is an file found where the method was called from
        if (request()->method !== 'GET' || !isset($debugTrace[0]['file'])) {
            return $this;
        }

        // get file path 
        $file = $debugTrace[0]['file'];

        // get last modified based on if there is an active cache
        $lastModified = request()->headers('If-Modified-Since') ? (new DateTime(request()->headers('If-Modified-Since')))->getTimestamp() : filemtime($file);

        // check if file is not modified between new last modified date
        if (filemtime($file) > $lastModified) {
            // set last modified to file modified date
            $lastModified = filemtime($file);
        }

        // check if there is an current http cache make sure the max-age us incrementing
        // based on the lifetime and last modified date
        [$newLifeTime, $lastModified] = $this->holdCurrentCache($lifeTime, $lastModified);

        // make etag to match for specific content
        $etag = '"' . md5(clearInjections($identifier . $file . $lastModified)) . '"';

        // add reponse headers
        response()->headers([
            // Set Cache-Control header
            'Cache-Control' => $type . ', max-age=' . max($newLifeTime, 0) . ', must-revalidate',
            // set last Modified
            'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified),
            // set expire date
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified + $lifeTime),
            // Set ETag header
            'ETag' => $etag
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
     * When the max-age = 0 then the http-cache will be removed to force renew cache modified at + lifetime
     * @param int $lifeTime
     * @param int $lastModified
     */

    private function holdCurrentCache(int $lifeTime, int $lastModified): array
    {
        // check if there exist and cache
        if (!request()->headers('If-Modified-Since')) {
            // return null as lifeTime
            return [
                $lifeTime,
                $lastModified
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
                'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT'
            ]);

            // set last modified to now
            $lastModified = (new DateTime())->getTimestamp();
        }

        // return new information for cache
        return [
            $lifeTime,
            $lastModified
        ];
    }

    /**
     * function data
     * cache/get cached data
     * @param string $identifier(unique identifier)
     * @param \Closure $closure
     * @param int $lifeTime = 3600
     **/

    public function data(string $identifier, \Closure $closure, int $lifeTime = 3600)
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
        file_put_contents($this->cacheFolderPath . $cacheInfo->fileName, json_encode($data));

        // put inside config
        $this->putInsideConfig(['identifier' => $cacheInfo->identifier, 'fileName' => $cacheInfo->fileName, 'lastModified' => time(), 'lifeTime' => $lifeTime]);

        // return data
        return $data;
    }

    /**
     * function get
     * get single cache file if exists
     * @param string $identifier(unique identifier)
     * @return boolean|object
     **/

    public function get(string $identifier)
    {
        // krijg cache infor bij identifier
        $cacheInfo = $this->getCacheItemInfo($identifier);

        // return data als er een bestand is gevonden
        return $cacheInfo->fileFound ? $cacheInfo->data : false;
    }

    /**
     * function remove
     * removes cache file
     * @param string $identifier(unique identifier)
     * @return void
     **/

    public function remove(string $identifier, bool $isFlushed = false)
    {
        // krijg cache infor bij identifier
        $cacheInfo = $this->getCacheItemInfo($identifier);

        // kijk of er een cache bestand is gevonden
        if ($cacheInfo->fileFound) {
            // verwijder cache file van cache
            unlink($this->cacheFolderPath . $cacheInfo->fileName);
        }

        // update cacheConfig and removed wrong cache files
        if (!$isFlushed) {
            $this->flushEndOfLiveTimeCacheItems();
        }
    }

    /**
     * function getCacheItemInfo
     * gets inforamtion cache information by identifier
     * @param string $identifier(unique identifier)
     * @return object
     **/

    private function getCacheItemInfo(string $identifier)
    {
        // generate hash for identifier
        $identifier = $this->generateFileName($identifier);

        // default returndata template
        $returnData = (object)[
            'data' => null,
            'lastModified' => null,
            'fileName' => $identifier . '.json',
            'identifier' => $identifier,
            'fileFound' => null
        ];

        // check if file exists
        if (!file_exists($this->cacheConfigFilePath)) {
            // update fileFound prop
            $returnData->fileFound = false;
            // return data
            return $returnData;
        }

        // krijg cache config data
        $cacheConfigData = json_decode(file_get_contents($this->cacheConfigFilePath));

        // kijk of er cache data bestaat
        if (empty($cacheConfigData) || !is_array($cacheConfigData)) {
            // update fileFound prop
            $returnData->fileFound = false;
            // return data
            return $returnData;
        }

        // vind cache item by identifier
        $cacheItem = array_filter($cacheConfigData, function ($cacheItem) use ($identifier) {
            return isset($cacheItem->identifier) && $cacheItem->identifier === $identifier;
        });

        // kijk of er een cacheItem is gevonden
        if (empty($cacheItem)) {
            // update fileFound prop
            $returnData->fileFound = false;
            // return data
            return $returnData;
        }

        // get cacheItem info
        $cacheItem = $cacheItem[array_key_first($cacheItem)];

        //  kijk of lifetime is verlopen
        if ($cacheItem->lastModified + $cacheItem->lifeTime <= time()) {
            // verwijder cache file
            unlink($this->cacheFolderPath . $cacheItem->fileName);
            // update return data
            $returnData->fileFound = false;
            // krijg return data van closure
            return $returnData;
        }

        // kijk of bestand gevonden konden worden
        // update return data
        $returnData->fileFound = file_exists($this->cacheFolderPath . $cacheItem->fileName);
        $returnData->data = $returnData->fileFound ? json_decode(file_get_contents($this->cacheFolderPath . $cacheItem->fileName)) : null;

        // return data
        return $returnData;
    }

    /**
     * function flushEndOfLiveTimeCacheItems
     * checkes if cacheFiles are still valid and cacheConfig is up to date
     * @return boolean|\DateTime
     **/

    public function flushEndOfLiveTimeCacheItems()
    {
        // kijk of cache config file exists
        if (!file_exists($this->cacheConfigFilePath)) {
            return false;
        }

        // decode json to array/object
        $cacheItems = json_decode(file_get_contents($this->cacheConfigFilePath));

        // loop through alle cache items and check if the lifetime didn't passed
        foreach ($cacheItems as $key => $cacheItem) {
            // check if lifeTime is passed
            if (!isset($cacheItem->lastModified, $cacheItem->lifeTime, $cacheItem->identifier) || !file_exists($this->cacheFolderPath . $cacheItem->fileName) || $cacheItem->lastModified + $cacheItem->lifeTime <= time()) {
                // verwijder cache item van array
                unset($cacheItems[$key]);
                // check if file exists then remove cache file
                $this->remove($cacheItem->identifier, true);
            }
        }

        // wrong files to filter out
        $wrongFiles = ['..', '.', '.DS_Store', '__cacheConfig.json'];

        // get all files accept '..' and '.'
        $files = array_filter(scandir(SERVER_ROOT . '/../cache'), fn ($file) => !in_array($file, $wrongFiles));

        // get all identifiers from cacheConfig items
        $identifiers = array_column($cacheItems, 'identifier');

        // loop trough all files
        foreach ($files as $file) {
            // kijk of file niet in cache config identifiers array staat
            if (!in_array(str_replace('.json', '', $file), $identifiers)) {
                // verwijder bestand
                unlink($this->cacheFolderPath . $file);
            }
        }

        // put new content to cache config file
        $this->putInsideConfig($cacheItems, false);

        // return last updated date
        return (new \DateTime())->format('Y-m-d H:i:s');
    }

    /**
     * function putInsideConfig
     * updates cache config based on cache data files
     * @return void
     **/

    private function putInsideConfig($data, bool $mergeOldData = true): void
    {
        // behoud oude email
        $oldData = [];

        // kijk of cacheConfig file bestaat
        if ($mergeOldData && file_exists($this->cacheConfigFilePath)) {
            $oldData = json_decode(file_get_contents($this->cacheConfigFilePath)) ?? [];
        }

        // insert new data
        file_put_contents($this->cacheConfigFilePath, json_encode($mergeOldData ? [$data, ...$oldData] : $data));
    }

    /**
     * function generateFileName
     * makes hashed string to make filenames more random/secure
     * @param string $identifier
     * @return string
     **/

    private function generateFileName(string $identifier): string
    {
        return md5($identifier);
    }

    /**
     * function flush
     * This method will delete all cache files
     * @return self
     */

    public function flush(): self
    {
        // check if cache location exists
        if (!file_exists($this->cacheFolderPath)) {
            return false;
        }

        // wrong files to filter out
        $wrongFiles = ['..', '.'];

        // get all files accept '..' and '.'
        $files = array_filter(scandir(SERVER_ROOT . '/../cache'), fn ($file) => !in_array($file, $wrongFiles));

        // loop trough all files and delete them
        foreach ($files as $file) {
            unlink($this->cacheFolderPath . $file);
        }

        // return self
        return $this;
    }
}
