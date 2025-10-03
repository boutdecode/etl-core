<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\CQS\Application\Exception;

use BoutDeCode\ETLCoreBundle\CQS\Application\Exception\QueryHandlerException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QueryHandlerExceptionTest extends TestCase
{
    #[Test]
    public function constructShouldFormatMessageWithFileAndExceptionMessage(): void
    {
        $originalException = new \Exception('Original query error message', 500);
        $customMessage = 'Custom query error occurred';

        $exception = new QueryHandlerException($customMessage, $originalException);

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
        $originalException = new \Exception('Query test message', 0);
        $customMessage = 'Query handler error';

        $exception = new QueryHandlerException($customMessage, $originalException);

        $this->assertSame(0, $exception->getCode());
    }

    #[Test]
    public function getFirstShouldReturnDeepestException(): void
    {
        $deepestException = new \RuntimeException('Deepest query error');
        $middleException = new \LogicException('Middle query error', 0, $deepestException);
        $topException = new \Exception('Top query error', 0, $middleException);

        $queryException = new QueryHandlerException('Query failed', $topException);

        $first = $queryException->getFirst();

        $this->assertSame($deepestException, $first);
    }

    #[Test]
    public function getFirstShouldReturnSelfWhenNoPreviousException(): void
    {
        $originalException = new \Exception('Single query error');
        $queryException = new QueryHandlerException('Query failed', $originalException);

        $first = $queryException->getFirst();

        // Should return the deepest, which in this case is the original exception
        $this->assertSame($originalException, $first);
    }

    #[Test]
    public function exceptionShouldExtendBaseException(): void
    {
        $originalException = new \Exception('Test');
        $queryException = new QueryHandlerException('Test', $originalException);

        $this->assertInstanceOf(\Exception::class, $queryException);
    }

    #[Test]
    public function constructShouldPreserveOriginalStackTrace(): void
    {
        $originalException = new \Exception('Original query error');
        $queryException = new QueryHandlerException('Wrapper query error', $originalException);

        $this->assertSame($originalException, $queryException->getPrevious());
        $this->assertStringContainsString('Original query error', $queryException->getMessage());
    }

    #[Test]
    public function getFirstWithComplexChainShouldReturnCorrectException(): void
    {
        $level1 = new \RuntimeException('Query Level 1');
        $level2 = new \LogicException('Query Level 2', 0, $level1);
        $level3 = new \InvalidArgumentException('Query Level 3', 0, $level2);
        $level4 = new \Exception('Query Level 4', 0, $level3);

        $queryException = new QueryHandlerException('Query failed', $level4);

        $first = $queryException->getFirst();

        $this->assertSame($level1, $first);
        $this->assertSame('Query Level 1', $first->getMessage());
    }
}
