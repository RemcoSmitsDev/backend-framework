<?php

namespace Framework\Cache;

class Cache
{
    /**
    * $cacheConfigFilePath
    **/

    private $cacheFolderPath = '';
    private $cacheConfigFilePath = '';

    /**
    * @param int $remeberTime = 86400
    **/

    private int $rememberTime = 86400;


    public function __construct()
    {
        $this->cacheFolderPath = SERVER_ROOT.'/../cache/';
        $this->cacheConfigFilePath = $this->cacheFolderPath.'__cacheConfig.json';
    }

    /**
    * function remember
    * @param int $remeberTime = 86400
    * @return self
    **/

    public function remember(int $rememberTime = 86400)
    {
        $this->rememberTime = $rememberTime;
        return $this;
    }

    /**
    * function file
    * @param string $file(__FILE__)
    * @param string $extraInformationToEtagName = ''
    * @return void
    **/

    public function file(string $file, string $extraInformationToEtagName = '')
    {
        // check if cache can be on
        if (!config()::ALLOW_HTTP_CACHE) {
            return false;
        }
        // Get last modification time of the current PHP file
        $fileLastModifiedTime = filemtime($file);

        // Combine both to generate a unique ETag for a unique content
        // Specification says ETag should be specified within double quotes
        $etag = '"' . md5(clearInjections($file.$fileLastModifiedTime.$extraInformationToEtagName)) . '"';

        // Set Cache-Control header
        header('Cache-Control: public, max-age=' . $this->rememberTime . ', must-revalidate');

        // format lastModified
        $lastModified = gmdate('D, d M Y H:i:s', $fileLastModifiedTime).' GMT';

        // set last Modified
        header("Last-Modified: {$lastModified}");

        // format expires
        $expires = gmdate('D, d M Y H:i:s', time() + $this->rememberTime).' GMT';
        // set expire date
        header("Expires: {$expires}");

        // Set ETag header
        header('ETag: ' . $etag);

        // Check whether browser had sent a HTTP_IF_NONE_MATCH request header
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
            // If HTTP_IF_NONE_MATCH is same as the generated ETag => content is the same as browser cache
            // So send a 304 Not Modified response header and exit
            header('HTTP/1.1 304 Not Modified', true, 304);
            exit();
        }
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
        // kijken of cache gebruikt mag worden
        if (!config()::ALLOW_FILE_CACHE) {
            return $closure();
        }
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
        file_put_contents($this->cacheFolderPath.$cacheInfo->fileName, json_encode($data));

        // put inside config
        $this->putInsideConfig(['identifier' => $cacheInfo->identifier,'fileName' => $cacheInfo->fileName, 'lastModified' => time(), 'lifeTime' => $lifeTime]);

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
            unlink($this->cacheFolderPath.$cacheInfo->fileName);
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
          'fileName' => $identifier.'.json',
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
            unlink($this->cacheFolderPath.$cacheItem->fileName);
            // update return data
            $returnData->fileFound = false;
            // krijg return data van closure
            return $returnData;
        }

        // kijk of bestand gevonden konden worden
        // update return data
        $returnData->fileFound = file_exists($this->cacheFolderPath.$cacheItem->fileName);
        $returnData->data = $returnData->fileFound ? json_decode(file_get_contents($this->cacheFolderPath.$cacheItem->fileName)) : null;

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
            if (!isset($cacheItem->lastModified, $cacheItem->lifeTime, $cacheItem->identifier) || !file_exists($this->cacheFolderPath.$cacheItem->fileName) || $cacheItem->lastModified + $cacheItem->lifeTime <= time()) {
                // verwijder cache item van array
                unset($cacheItems[$key]);
                // check if file exists then remove cache file
                $this->remove($cacheItem->identifier, true);
            }
        }

        // wrong files to filter out
        $wrongFiles = ['..','.','.DS_Store','__cacheConfig.json'];

        // get all files accept '..' and '.'
        $files = array_filter(scandir(SERVER_ROOT.'/../cache'), fn ($file) => !in_array($file, $wrongFiles));

        // get all identifiers from cacheConfig items
        $identifiers = array_column($cacheItems, 'identifier');

        // loop trough all files
        foreach ($files as $file) {
            // kijk of file niet in cache config identifiers array staat
            if (!in_array(str_replace('.json', '', $file), $identifiers)) {
                // verwijder bestand
                unlink($this->cacheFolderPath.$file);
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

    private function putInsideConfig($data, bool $mergeOldData = true)
    {
        // behoud oude email
        $oldData = [];

        // kijk of cacheConfig file bestaat
        if ($mergeOldData && file_exists($this->cacheConfigFilePath)) {
            $oldData = json_decode(file_get_contents($this->cacheConfigFilePath)) ?? [];
        }

        // insert new data
        file_put_contents($this->cacheConfigFilePath, json_encode($mergeOldData ? [$data,...$oldData] : $data));
    }

    /**
    * function generateFileName
    * makes hashed string to make filenames more random/secure
    * @param string $identifier
    * @return string
    **/

    private function generateFileName(string $identifier)
    {
        return md5($identifier);
    }
}
