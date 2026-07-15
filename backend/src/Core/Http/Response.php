<?php

declare(strict_types=1);

namespace AetherLink\Core\Http;

class Response
{
    /**
     *
     * @param array<string, string> $headers
     */

    public function __construct(
        private string $content,
        private int $statusCode = 200,
        private array $headers = []
    ) {
        // Inforce JSON baseline content-type if not explicitly overridden
        if (!isset($this->headers['Content-Type'])) {
            $this->headers['Content-Type'] = 'application/json; charset=utf-8';
        }
    }

    public static function json(array $data, int $statusCode = 200, array $headers = []): self
    {
        return new self(json_encode($data, JSON_THROW_ON_ERROR), $statusCode, $headers);
    }

    public function send(): void
    {
        //1. Emit HTTP status Line 
        http_response_code($this->statusCode);

        //2. Emit Configured Header Matrix
        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        //3. Dump Content Payload Into Output Buffer
        echo $this->content;
    }
}
