<?php

namespace AdamDziuk\LaravelAgentCost\Tests\Fixtures;

use Illuminate\Broadcasting\Channel;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\QueuedAgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use LogicException;

/**
 * A minimal `Agent` implementation used only to exercise `AgentPrompt` and
 * `RecordAgentCost` in tests. None of its methods are meant to actually be
 * called; only its class identity matters.
 */
class FakeAgent implements Agent
{
    public function instructions(): string
    {
        return 'You are a fake agent used only in tests.';
    }

    public function prompt(
        Decisions|string $prompt,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null,
        ?int $timeout = null,
    ): AgentResponse {
        throw new LogicException(self::class.' is not meant to be actually prompted.');
    }

    public function stream(
        Decisions|string $prompt,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null,
        ?int $timeout = null,
    ): StreamableAgentResponse {
        throw new LogicException(self::class.' is not meant to be actually prompted.');
    }

    public function queue(
        Decisions|string $prompt,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): QueuedAgentResponse {
        throw new LogicException(self::class.' is not meant to be actually prompted.');
    }

    public function broadcast(
        Decisions|string $prompt,
        Channel|array $channels,
        array $attachments = [],
        bool $now = false,
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): StreamableAgentResponse {
        throw new LogicException(self::class.' is not meant to be actually prompted.');
    }

    public function broadcastNow(
        Decisions|string $prompt,
        Channel|array $channels,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): StreamableAgentResponse {
        throw new LogicException(self::class.' is not meant to be actually prompted.');
    }

    public function broadcastOnQueue(
        Decisions|string $prompt,
        Channel|array $channels,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): QueuedAgentResponse {
        throw new LogicException(self::class.' is not meant to be actually prompted.');
    }
}
