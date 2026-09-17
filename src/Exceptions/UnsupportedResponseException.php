<?php

namespace AdamDziuk\LaravelAgentCost\Exceptions;

use RuntimeException;

class UnsupportedResponseException extends RuntimeException
{
    public static function for(object $response): self
    {
        return new self(sprintf(
            'Unable to determine the cost of a [%s]: it does not expose token usage information.',
            $response::class,
        ));
    }
}
