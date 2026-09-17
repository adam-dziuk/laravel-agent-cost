<?php

namespace AdamDziuk\LaravelAgentCost\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static float tokens(string $model, int $inputTokens, int $outputTokens)
 * @method static float for(object $response)
 *
 * @see \AdamDziuk\LaravelAgentCost\AiCost
 */
class AiCost extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AdamDziuk\LaravelAgentCost\AiCost::class;
    }
}
