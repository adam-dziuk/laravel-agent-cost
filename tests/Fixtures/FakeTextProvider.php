<?php

namespace AdamDziuk\LaravelAgentCost\Tests\Fixtures;

use Laravel\Ai\Contracts\Gateway\StepTextGateway;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Gateway\TextGenerationLoop;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use LogicException;

/**
 * A minimal `TextProvider` implementation used only to satisfy
 * `AgentPrompt`'s constructor in tests. None of its methods are meant to
 * actually be called.
 */
class FakeTextProvider implements TextProvider
{
    public function prompt(AgentPrompt $prompt): AgentResponse
    {
        throw new LogicException(self::class.' is not meant to be actually used.');
    }

    public function stream(AgentPrompt $prompt): StreamableAgentResponse
    {
        throw new LogicException(self::class.' is not meant to be actually used.');
    }

    public function useTextGateway(StepTextGateway $gateway): self
    {
        return $this;
    }

    public function textGenerationLoop(): TextGenerationLoop
    {
        throw new LogicException(self::class.' is not meant to be actually used.');
    }

    public function defaultTextModel(): string
    {
        return 'fake-model';
    }

    public function cheapestTextModel(): string
    {
        return 'fake-model';
    }

    public function smartestTextModel(): string
    {
        return 'fake-model';
    }

    public function name(): string
    {
        return 'fake';
    }

    public function driver(): string
    {
        return 'fake';
    }

    public function providerCredentials(): array
    {
        return [];
    }

    public function additionalConfiguration(): array
    {
        return [];
    }
}
