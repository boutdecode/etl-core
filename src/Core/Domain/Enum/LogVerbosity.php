<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Enum;

use Psr\Log\LogLevel;

enum LogVerbosity: string
{
    case NONE = 'none';
    case ERROR = 'error';
    case WARNING = 'warning';
    case INFO = 'info';
    case DEBUG = 'debug';

    public function allows(string $psrLevel): bool
    {
        return match ($psrLevel) {
            LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR => $this !== self::NONE,
            LogLevel::WARNING => $this->rank() >= self::WARNING->rank(),
            LogLevel::NOTICE, LogLevel::INFO => $this->rank() >= self::INFO->rank(),
            LogLevel::DEBUG => $this === self::DEBUG,
            default => false,
        };
    }

    public function isDebug(): bool
    {
        return $this === self::DEBUG;
    }

    private function rank(): int
    {
        return match ($this) {
            self::NONE => 0,
            self::ERROR => 1,
            self::WARNING => 2,
            self::INFO => 3,
            self::DEBUG => 4,
        };
    }
}
