<?php

namespace AdamDziuk\LaravelAgentCost\Exceptions;

use RuntimeException;

class UnknownModelException extends RuntimeException
{
    public static function forModel(string $model): self
    {
        return new self("No pricing data is available for model \"{$model}\". Run `php artisan ai-prices:sync` or add a price override for it.");
    }

    public static function missingModel(): self
    {
        return new self('Unable to determine the cost of the response: it does not carry a model name.');
    }
}
