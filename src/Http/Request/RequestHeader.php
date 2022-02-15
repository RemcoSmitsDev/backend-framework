<?php

namespace Framework\Http\Request;

final class RequestHeader extends GetAble
{
    /**
     * @var array<string, string>
     */
    protected array $headers = [];

    /**
     * @param ServerHeader $server
     */
    public function __construct(
        private ServerHeader $server
    ) {
        // init getable
        parent::__construct('headers');

        // merge all headers
        $this->headers = function_exists('getallheaders') ? getallheaders() : $server->all();
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function __get(string $name): ?string
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->headers[$name] = $value;
    }
}
