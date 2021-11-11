<?php

namespace Framework\Interfaces\Http;

/**
 *
 */
interface ResponseInterface
{
    public function json(array $responseData): self;

    public function text(string $responseData): self;

    public function headers(array $headers): self;

    public function code(int $responseCode = 200): self;
}
