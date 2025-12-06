<?php

namespace App\Enum\User;

enum Status: string
{
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
    case UNVERIFIED = 'unverified';

    public function canTransitionTo(Status $next): bool {
        return match ($this) {
            self::UNVERIFIED => \in_array($next, [self::ACTIVE, self::BLOCKED]),
            self::ACTIVE => \in_array($next, [self::BLOCKED]),
            self::BLOCKED => false,
        };
    }
}