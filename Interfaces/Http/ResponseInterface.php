<?php

namespace Framework\Interfaces\Http;

/**
 *
 */
interface ResponseInterface
{
    public static function json(array $responseData): self;

    public static function text(string $responseData): self;

    public static function headers(array $headers): self;

    public static function code(int $responseCode = 200): self;
}
