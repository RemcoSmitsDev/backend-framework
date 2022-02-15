<?php

namespace Framework\Interfaces\Http;

interface ResponseInterface
{
    /**
     * @param array $responseData
     *
     * @return $this
     */
    public function json(array $responseData): self;

    /**
     * @param string $responseData
     *
     * @return $this
     */
    public function text(string $responseData): self;

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function headers(array $headers): self;

    /**
     * @param int $responseCode
     *
     * @return $this
     */
    public function code(int $responseCode = 200): self;
}
