<?php

namespace Framework\Config;

class BaseConfig
{
    private string $serverRoot;

    public function __construct()
    {
        $this->serverRoot = $_SERVER['DOCUMENT_ROOT'];
    }
}
