<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class DiscordApiException extends Exception
{
    public static function fromResponse(string $method, string $uri, Response $response): self
    {
        return new self(sprintf(
            'Discord API %s %s failed with status %d: %s',
            $method,
            $uri,
            $response->status(),
            $response->body(),
        ));
    }
}
