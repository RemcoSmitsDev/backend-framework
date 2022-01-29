<?php

namespace Framework\Http;

trait RequestParser
{
	protected function parseProtocol(): string
	{
		return $this->server->get('SERVER_PROTOCOL');
	}

	protected function parseHost(): string
	{
		return $this->server->get('HTTP_HOST');
	}

	protected function parseUri(): string
	{
		return parse_url(
			rawurldecode($this->url()),
			PHP_URL_PATH
		) ?? '';
	}

	protected function parseQuery(): string
	{
		return parse_url(
			rawurldecode($this->url()),
			PHP_URL_QUERY
		) ?? '';
	}
}
