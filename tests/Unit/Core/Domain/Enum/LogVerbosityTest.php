<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Core\Domain\Enum;

use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\LogVerbosity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class LogVerbosityTest extends TestCase
{
    /**
     * @return iterable<string, array{LogVerbosity, string, bool}>
     */
    public static function provideAllows(): iterable
    {
        yield 'none/error' => [LogVerbosity::NONE, LogLevel::ERROR, false];
        yield 'error/error' => [LogVerbosity::ERROR, LogLevel::ERROR, true];
        yield 'error/info' => [LogVerbosity::ERROR, LogLevel::INFO, false];
        yield 'warning/warning' => [LogVerbosity::WARNING, LogLevel::WARNING, true];
        yield 'warning/info' => [LogVerbosity::WARNING, LogLevel::INFO, false];
        yield 'info/info' => [LogVerbosity::INFO, LogLevel::INFO, true];
        yield 'info/debug' => [LogVerbosity::INFO, LogLevel::DEBUG, false];
        yield 'debug/debug' => [LogVerbosity::DEBUG, LogLevel::DEBUG, true];
        yield 'debug/info' => [LogVerbosity::DEBUG, LogLevel::INFO, true];
    }

    #[Test]
    #[DataProvider('provideAllows')]
    public function itFiltersLevels(LogVerbosity $verbosity, string $level, bool $expected): void
    {
        self::assertSame($expected, $verbosity->allows($level));
    }

    #[Test]
    public function onlyDebugIsDebug(): void
    {
        self::assertTrue(LogVerbosity::DEBUG->isDebug());
        self::assertFalse(LogVerbosity::INFO->isDebug());
    }
}
