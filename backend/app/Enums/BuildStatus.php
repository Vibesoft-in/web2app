<?php

namespace App\Enums;

enum BuildStatus: string
{
    case QUEUED = 'queued';
    case PREPARING = 'preparing';
    case BUILDING = 'building';
    case SIGNING = 'signing';
    case READY = 'ready';
    case FAILED = 'failed';

    /**
     * Determine if this is a final terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::READY, self::FAILED], true);
    }

    /**
     * Determine if this build is actively running or waiting.
     */
    public function isActive(): bool
    {
        return ! $this->isTerminal();
    }

    /**
     * Determine whether transition from current status to $next status is permitted.
     */
    public function canTransitionTo(BuildStatus $next): bool
    {
        if ($next === self::FAILED) {
            return ! $this->isTerminal();
        }

        return match ($this) {
            self::QUEUED => $next === self::PREPARING,
            self::PREPARING => $next === self::BUILDING,
            self::BUILDING => $next === self::SIGNING,
            self::SIGNING => $next === self::READY,
            self::READY, self::FAILED => false,
        };

    }
}
