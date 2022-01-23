<?php

namespace Framework\Http;

use Curl\Curl;
use Framework\Debug\Debug;

class Http extends Curl
{
	public function __destruct()
	{
		Debug::add('request', $this);
	}
}
