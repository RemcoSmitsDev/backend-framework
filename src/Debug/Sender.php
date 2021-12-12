#!/usr/local/bin/php
<?php

require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../helperFunctions.php');

Http()->post('http://localhost:9890', json_encode(unserialize(base64_decode($argv[1]))));
