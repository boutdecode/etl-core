<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\CQS\Application\Exception;

use BoutDeCode\ETLCoreBundle\CQS\Application\Exception\CommandHandlerException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CommandHandlerExceptionTest extends TestCase
{
    #[Test]
    public function constructShouldFormatMessageWithFileAndExceptionMessage(): void
    {
        $originalException = new \Exception('Original error message', 500);
        $customMessage = 'Custom error occurred';

        $exception = new CommandHandlerException($customMessage, $originalException);

        $expectedMessage = sprintf(
            '%s in %s: %s',
            $customMessage,
            $originalException->getFile(),
            $originalException->getMessage()
        );

        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame($originalException, $exception->getPrevious());
    }

    #[Test]
    public function constructShouldHandleExceptionWithZeroCode(): void
    {
        $originalException = new \Exception('Test message', 0);
        $customMessage = 'Handler error';

        $exception = new CommandHandlerException($customMessage, $originalException);

        $this->assertSame(0, $exception->getCode());
    }

    #[Test]
    public function getFirstShouldReturnDeepestException(): void
    {
        $deepestException = new \RuntimeException('Deepest error');
        $middleException = new \LogicException('Middle error', 0, $deepestException);
        $topException = new \Exception('Top error', 0, $middleException);

        $commandException = new CommandHandlerException('Command failed', $topException);

        $first = $commandException->getFirst();

        $this->assertSame($deepestException, $first);
    }

    #[Test]
    public function getFirstShouldReturnSelfWhenNoPreviousException(): void
    {
        $originalException = new \Exception('Single error');
        $commandException = new CommandHandlerException('Command failed', $originalException);

        $first = $commandException->getFirst();

        // Should return the deepest, which in this case is the original exception
        $this->assertSame($originalException, $first);
    }

    #[Test]
    public function exceptionShouldExtendBaseException(): void
    {
        $originalException = new \Exception('Test');
        $commandException = new CommandHandlerException('Test', $originalException);

        $this->assertInstanceOf(\Exception::class, $commandException);
    }

    #[Test]
    public function constructShouldPreserveOriginalStackTrace(): void
    {
        $originalException = new \Exception('Original error');
        $commandException = new CommandHandlerException('Wrapper error', $originalException);

        $this->assertSame($originalException, $commandException->getPrevious());
        $this->assertStringContainsString('Original error', $commandException->getMessage());
    }

    #[Test]
    public function getFirstWithComplexChainShouldReturnCorrectException(): void
    {
        $level1 = new \RuntimeException('Level 1');
        $level2 = new \LogicException('Level 2', 0, $level1);
        $level3 = new \InvalidArgumentException('Level 3', 0, $level2);
        $level4 = new \Exception('Level 4', 0, $level3);

        $commandException = new CommandHandlerException('Command failed', $level4);

        $first = $commandException->getFirst();

        $this->assertSame($level1, $first);
        $this->assertSame('Level 1', $first->getMessage());
    }
}
