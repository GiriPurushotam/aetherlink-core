<?php

declare(strict_types=1);

namespace AetherLink\Core\Http;

/**
 * @param array<string, mixed> $queryParameters URL query string variables ($_GET)
 * @param array<string, mixed> $bodyParamaeters parsed JSON payload or form data ($_POST)
 * @param array<string, string> $headers Reconstructed HTTP request headers
 */
readonly final class Request
{
    public function __construct(
        public string $uri,
        public string $method,
        public array $queryParameters,
        public array $bodyParameters,
        public array $headers
    ) {}

    /**
     * Factory constructor to forge a Request directly out of PHP's server environment globals.
     */

    public static function createFromGlobals(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        //Parse raw incoming JSON bodies typical for modern API endpoints.
        $rawInput = file_get_contents('php://input');
        $body     = [];
        if (!empty($rawInput)) {
            $decoded = json_decode($rawInput, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        //Extract system request headers.
        /**
         * @var array<string, string> $headers
         */
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }

        return new self(
            uri: $uri,
            method: $method,
            queryParameters: $_GET,
            bodyParameters: array_merge($_POST, $body),
            headers: $headers
        );
    }
}
