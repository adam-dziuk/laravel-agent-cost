<?php

namespace AdamDziuk\LaravelAgentCost\Tests\Fixtures;

/**
 * A backed enum used to prove the dashboard's admin check compares
 * enum-cast user attributes by their backing value.
 */
enum Role: string
{
    case Admin = 'admin';
    case Editor = 'editor';
}
