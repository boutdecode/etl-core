<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Infrastructure\Instrumentation;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\LogVerbosity;
use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Instrumentation\Logger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class LoggerTest extends TestCase
{
    #[Test]
    public function infoIsSilentWhenLevelIsError(): void
    {
        $psr = $this->createMock(LoggerInterface::class);
        $psr->expects(self::never())->method('info');

        (new Logger($psr, $this->createStub(NormalizerInterface::class), LogVerbosity::ERROR))
            ->info('msg', new Context('payload'));
    }

    #[Test]
    public function errorIsSilentWhenLevelIsNone(): void
    {
        $psr = $this->createMock(LoggerInterface::class);
        $psr->expects(self::never())->method('error');

        (new Logger($psr, $this->createStub(NormalizerInterface::class), LogVerbosity::NONE))
            ->error('msg', new Context('payload'), new \RuntimeException('boom'));
    }

    #[Test]
    public function infoLogsSummaryWithoutNormalizingAtInfoLevel(): void
    {
        $normalizer = $this->createMock(NormalizerInterface::class);
        $normalizer->expects(self::never())->method('normalize');

        $psr = $this->createMock(LoggerInterface::class);
        $psr->expects(self::once())->method('info')->with(
            'msg',
            self::callback(static fn (array $c): bool => array_key_exists('pipeline', $c['context']) && ! isset($c['context']['input']))
        );

        (new Logger($psr, $normalizer, LogVerbosity::INFO))->info('msg', new Context('payload'));
    }

    #[Test]
    public function infoLogsFullContextAtDebugLevel(): void
    {
        $context = new Context('payload');
        $normalizer = $this->createMock(NormalizerInterface::class);
        $normalizer->expects(self::once())->method('normalize')->with($context)->willReturn([
            'full' => true,
        ]);

        $psr = $this->createMock(LoggerInterface::class);
        $psr->expects(self::once())->method('info')->with('msg', [
            'context' => [
                'full' => true,
            ],
        ]);

        (new Logger($psr, $normalizer, LogVerbosity::DEBUG))->info('msg', $context);
    }

    #[Test]
    public function errorIncludesTraceOnlyAtDebugLevel(): void
    {
        $normalizer = $this->createStub(NormalizerInterface::class);
        $captured = [];
        $psr = $this->createMock(LoggerInterface::class);
        $psr->method('error')->willReturnCallback(static function (string $m, array $c) use (&$captured): void {
            $captured[] = $c;
        });

        $exception = new \RuntimeException('boom');
        (new Logger($psr, $normalizer, LogVerbosity::INFO))->error('msg', new Context('p'), $exception);
        (new Logger($psr, $normalizer, LogVerbosity::DEBUG))->error('msg', new Context('p'), $exception);

        self::assertArrayNotHasKey('trace', $captured[0]);
        self::assertSame($exception->getTraceAsString(), $captured[1]['trace']);
    }
}
