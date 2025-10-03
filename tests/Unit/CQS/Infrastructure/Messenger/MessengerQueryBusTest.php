<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\CQS\Infrastructure\Messenger;

use BoutDeCode\ETLCoreBundle\CQS\Application\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Query\Query;
use BoutDeCode\ETLCoreBundle\CQS\Infrastructure\Messenger\MessengerQueryBus;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerQueryBusTest extends TestCase
{
    private MessageBusInterface $messageBus;

    private Logger $logger;

    private MessengerQueryBus $queryBus;

    protected function setUp(): void
    {
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->queryBus = new MessengerQueryBus($this->messageBus, $this->logger);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itSuccessfullyAsksQueryAndReturnsResponse(): void
    {
        $query = $this->createMock(Query::class);
        $expectedResponse = [
            'data' => [
                'id' => 1,
                'name' => 'Test',
            ],
            'count' => 1,
        ];
        $queryName = get_class($query);

        // Create an Envelope with the expected response as handler result
        $envelope = new Envelope($query);
        $envelope = $envelope->with(new \Symfony\Component\Messenger\Stamp\HandledStamp($expectedResponse, 'handler'));

        $this->logger
            ->expects($this->once())
            ->method('start')
            ->with($queryName, $query);

        $this->logger
            ->expects($this->once())
            ->method('success')
            ->with($queryName, $expectedResponse, true);

        $this->logger
            ->expects($this->never())
            ->method('error');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($query)
            ->willReturn($envelope);

        $result = $this->queryBus->ask($query);

        $this->assertSame($expectedResponse, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itHandlesHandlerFailedExceptionAndThrowsFirstException(): void
    {
        $query = $this->createMock(Query::class);
        $queryName = get_class($query);
        $originalException = new \RuntimeException('Query execution failed');

        $envelope = new Envelope($query);
        $handlerFailedException = new HandlerFailedException($envelope, [$originalException]);

        $this->logger
            ->expects($this->once())
            ->method('start')
            ->with($queryName, $query);

        $this->logger
            ->expects($this->never())
            ->method('success');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($queryName, $this->stringContains('Error during process'));

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($query)
            ->willThrowException($handlerFailedException);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Query execution failed');

        $this->queryBus->ask($query);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itThrowsQueryHandlerExceptionWhenExceptionsOccur(): void
    {
        $query = $this->createMock(Query::class);
        $queryName = get_class($query);
        $originalException = new \InvalidArgumentException('Invalid query parameters');

        $envelope = new Envelope($query);
        $handlerFailedException = new HandlerFailedException($envelope, [$originalException]);

        $this->logger
            ->expects($this->once())
            ->method('start');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($queryName, $this->stringContains('Error during process'));

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willThrowException($handlerFailedException);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid query parameters');

        $this->queryBus->ask($query);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itImplementsQueryBusInterface(): void
    {
        $this->assertInstanceOf(\BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Query\QueryBus::class, $this->queryBus);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itWorksWithNullableLogger(): void
    {
        $queryBusWithoutLogger = new MessengerQueryBus($this->messageBus, null);

        $query = $this->createMock(Query::class);
        $expectedResponse = 'query result';

        $envelope = new Envelope($query);
        $envelope = $envelope->with(new \Symfony\Component\Messenger\Stamp\HandledStamp($expectedResponse, 'handler'));

        $this->messageBus
            ->method('dispatch')
            ->willReturn($envelope);

        // With a null logger, null-safe calls should not throw any errors
        $result = $queryBusWithoutLogger->ask($query);

        $this->assertSame($expectedResponse, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itReturnsComplexQueryResult(): void
    {
        $query = $this->createMock(Query::class);
        $complexResult = [
            'users' => [
                [
                    'id' => 1,
                    'name' => 'John',
                    'email' => 'john@example.com',
                ],
                [
                    'id' => 2,
                    'name' => 'Jane',
                    'email' => 'jane@example.com',
                ],
            ],
            'pagination' => [
                'page' => 1,
                'per_page' => 10,
                'total' => 2,
            ],
            'filters' => [
                'status' => 'active',
            ],
        ];

        $envelope = new Envelope($query);
        $envelope = $envelope->with(new \Symfony\Component\Messenger\Stamp\HandledStamp($complexResult, 'handler'));

        $this->messageBus
            ->method('dispatch')
            ->willReturn($envelope);

        $this->logger->method('start');
        $this->logger->method('success');

        $result = $this->queryBus->ask($query);

        $this->assertSame($complexResult, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itHandlesExceptionChainCorrectly(): void
    {
        $query = $this->createMock(Query::class);
        $deepException = new \InvalidArgumentException('Deep query error');
        $middleException = new \LogicException('Middle query error', 0, $deepException);

        $envelope = new Envelope($query);
        $handlerFailedException = new HandlerFailedException($envelope, [$middleException]);

        $this->messageBus
            ->method('dispatch')
            ->willThrowException($handlerFailedException);

        $this->logger->method('start');
        $this->logger->method('error');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Deep query error');

        $this->queryBus->ask($query);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itPreservesQueryObjectIdentityThroughAsk(): void
    {
        $query = new class() implements Query {
            public string $criteria = 'test-criteria';
        };

        $response = [
            'found' => true,
        ];

        $envelope = new Envelope($query);
        $envelope = $envelope->with(new \Symfony\Component\Messenger\Stamp\HandledStamp($response, 'handler'));

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->identicalTo($query))
            ->willReturn($envelope);

        $this->logger->method('start');
        $this->logger->method('success');

        $result = $this->queryBus->ask($query);

        $this->assertSame($response, $result);
    }
}
