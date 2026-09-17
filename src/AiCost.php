<?php

namespace AdamDziuk\LaravelAgentCost;

use AdamDziuk\LaravelAgentCost\Exceptions\UnknownModelException;
use AdamDziuk\LaravelAgentCost\Exceptions\UnsupportedResponseException;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;

class AiCost
{
    public function __construct(private readonly PriceRepository $prices) {}

    /**
     * Calculate the USD cost of a number of input and output tokens for
     * the given model.
     *
     * @throws UnknownModelException if no pricing data is available for the model
     */
    public function tokens(string $model, int $inputTokens, int $outputTokens): float
    {
        $prices = $this->prices->find($model);

        if ($prices === null) {
            throw UnknownModelException::forModel($model);
        }

        return ($prices['input_cost_per_token'] ?? 0.0) * $inputTokens
            + ($prices['output_cost_per_token'] ?? 0.0) * $outputTokens;
    }

    /**
     * Calculate the USD cost of a laravel/ai response, such as a
     * TextResponse, AgentResponse, StructuredAgentResponse, ImageResponse,
     * TranscriptionResponse, or EmbeddingsResponse.
     *
     * @throws UnknownModelException if no pricing data is available for the response's model
     * @throws UnsupportedResponseException if the response exposes no token usage information
     */
    public function for(object $response): float
    {
        if (property_exists($response, 'usage') && property_exists($response, 'meta')
            && $response->usage instanceof Usage && $response->meta instanceof Meta) {
            return $this->costOfUsage($response->usage, $response->meta);
        }

        if (property_exists($response, 'tokens') && property_exists($response, 'meta')
            && is_int($response->tokens) && $response->meta instanceof Meta) {
            return $this->costOfTokenCount($response->tokens, $response->meta);
        }

        throw UnsupportedResponseException::for($response);
    }

    private function costOfUsage(Usage $usage, Meta $meta): float
    {
        $prices = $this->pricesFor($meta);

        $inputPrice = $prices['input_cost_per_token'] ?? 0.0;
        $outputPrice = $prices['output_cost_per_token'] ?? 0.0;
        $reasoningPrice = $prices['output_cost_per_reasoning_token'] ?? $outputPrice;
        $cacheReadPrice = $prices['cache_read_input_token_cost'] ?? 0.0;
        $cacheWritePrice = $prices['cache_creation_input_token_cost'] ?? 0.0;

        // Reasoning tokens are already included in `completionTokens`; only
        // the non-reasoning portion is billed at the regular output rate.
        $regularCompletionTokens = max($usage->completionTokens - $usage->reasoningTokens, 0);

        return $usage->promptTokens * $inputPrice
            + $regularCompletionTokens * $outputPrice
            + $usage->reasoningTokens * $reasoningPrice
            + $usage->cacheReadInputTokens * $cacheReadPrice
            + $usage->cacheWriteInputTokens * $cacheWritePrice;
    }

    private function costOfTokenCount(int $tokens, Meta $meta): float
    {
        $prices = $this->pricesFor($meta);

        return $tokens * ($prices['input_cost_per_token'] ?? 0.0);
    }

    /**
     * @return array<string, float>
     */
    private function pricesFor(Meta $meta): array
    {
        if ($meta->model === null) {
            throw UnknownModelException::missingModel();
        }

        $key = $meta->provider ? "{$meta->provider}/{$meta->model}" : $meta->model;

        return $this->prices->find($key) ?? throw UnknownModelException::forModel($meta->model);
    }
}
