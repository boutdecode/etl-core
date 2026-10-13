<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\CQS\Infrastructure\Instrumentation;

use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\LogVerbosity;
use BoutDeCode\ETLCoreBundle\CQS\Infrastructure\Instrumentation\Logger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class LoggerTest extends TestCase
{
    #[Test]
    public function startIsSilentWhenLevelIsError(): void
    {
        $psr = $this->createMock(LoggerInterface::class);
        $psr->expects(self::never())->method('info');

        (new Logger($psr, $this->createStub(NormalizerInterface::class), LogVerbosity::ERROR))
            ->start('cmd', [
                'a' => 1,
            ]);
    }

    #[Test]
    public function startOmitsPayloadAtInfoLevel(): void
    {
        $psr = $this->createMock(LoggerInterface::class);
        $psr->expects(self::once())->method('info')->with('cmd', []);

        (new Logger($psr, $this->createStub(NormalizerInterface::class), LogVerbosity::INFO))
            ->start('cmd', [
                'a' => 1,
            ]);
    }

    #[Test]
    public function successKeepsPayloadAtDebugLevel(): void
    {
        $psr = $this->createMock(LoggerInterface::class);
        $psr->expects(self::once())->method('info')->with('cmd.success', [
            'a' => 1,
        ]);

        (new Logger($psr, $this->createStub(NormalizerInterface::class), LogVerbosity::DEBUG))
            ->success('cmd', [
                'a' => 1,
            ]);
    }

    #[Test]
    public function errorIsLoggedAtErrorLevelButNotWhenNone(): void
    {
        $psr = $this->createMock(LoggerInterface::class);
        $psr->expects(self::once())->method('error')->with('cmd.error', [
            'reason' => 'why',
        ]);

        $normalizer = $this->createStub(NormalizerInterface::class);
        (new Logger($psr, $normalizer, LogVerbosity::ERROR))->error('cmd', 'why');
        (new Logger($psr, $normalizer, LogVerbosity::NONE))->error('cmd', 'why');
    }
}
