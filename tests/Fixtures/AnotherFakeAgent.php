<?php

namespace AdamDziuk\LaravelAgentCost\Tests\Fixtures;

/**
 * A second, distinct agent class used to prove that `AiCost::agent()`
 * scopes its totals to a single agent and doesn't mix in other agents'
 * recorded costs.
 */
class AnotherFakeAgent extends FakeAgent {}
