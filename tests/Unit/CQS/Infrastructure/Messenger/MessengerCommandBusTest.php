<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\CQS\Infrastructure\Messenger;

use BoutDeCode\ETLCoreBundle\CQS\Application\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\Command;
use BoutDeCode\ETLCoreBundle\CQS\Infrastructure\Messenger\MessengerCommandBus;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerCommandBusTest extends TestCase
{
    private MessageBusInterface $messageBus;

    private Logger $logger;

    private MessengerCommandBus $commandBus;

    protected function setUp(): void
    {
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->commandBus = new MessengerCommandBus($this->messageBus, $this->logger);
    }

    #[Test]
    public function itSuccessfullyDispatchesCommandAndReturnsResponse(): void
    {
        $command = $this->createStub(Command::class);
        $expectedResponse = [
            'success' => true,
            'data' => 'processed',
        ];
        $commandName = get_class($command);

        // Create an Envelope with the expected response as handler result
        $envelope = new Envelope($command);
        $envelope = $envelope->with(new \Symfony\Component\Messenger\Stamp\HandledStamp($expectedResponse, 'handler'));

        $this->logger
            ->expects($this->once())
            ->method('start')
            ->with($commandName, $command);

        $this->logger
            ->expects($this->once())
            ->method('success')
            ->with($commandName, $expectedResponse, true);

        $this->logger
            ->expects($this->never())
            ->method('error');

        // Mock the messenger bus to return the envelope
        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willReturn($envelope);

        $result = $this->commandBus->dispatch($command);

        $this->assertSame($expectedResponse, $result);
    }

    #[Test]
    public function itHandlesHandlerFailedExceptionAndThrowsFirstException(): void
    {
        $command = $this->createStub(Command::class);
        $commandName = get_class($command);
        $originalException = new \RuntimeException('Original error');

        $envelope = new Envelope($command);
        $handlerFailedException = new HandlerFailedException($envelope, [$originalException]);

        $this->logger
            ->expects($this->once())
            ->method('start')
            ->with($commandName, $command);

        $this->logger
            ->expects($this->never())
            ->method('success');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($commandName, $this->stringContains('Error during process'));

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willThrowException($handlerFailedException);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Original error');

        $this->commandBus->dispatch($command);
    }

    #[Test]
    public function itThrowsCommandHandlerExceptionWhenExceptionsOccur(): void
    {
        $command = $this->createStub(Command::class);
        $commandName = get_class($command);
        $originalException = new \RuntimeException('Handler error');

        $envelope = new Envelope($command);
        $handlerFailedException = new HandlerFailedException($envelope, [$originalException]);

        $this->logger
            ->expects($this->once())
            ->method('start');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($commandName, $this->stringContains('Error during process'));

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willThrowException($handlerFailedException);

        // Should throw the first exception from the chain
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Handler error');

        $this->commandBus->dispatch($command);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itImplementsCommandBusInterface(): void
    {
        $this->assertInstanceOf(\BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus::class, $this->commandBus);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itLogsCommandExecutionWithCorrectParameters(): void
    {
        $command = new class() implements Command {};
        $expectedResponse = 'test-response';
        $commandName = get_class($command);

        $envelope = new Envelope($command);
        $envelope = $envelope->with(new \Symfony\Component\Messenger\Stamp\HandledStamp($expectedResponse, 'handler'));

        $this->logger
            ->expects($this->once())
            ->method('start')
            ->with($commandName, $command);

        $this->logger
            ->expects($this->once())
            ->method('success')
            ->with($commandName, $expectedResponse, true);

        $this->messageBus
            ->method('dispatch')
            ->willReturn($envelope);

        $result = $this->commandBus->dispatch($command);

        $this->assertSame($expectedResponse, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itReturnsComplexResponseFromSymfonyMessenger(): void
    {
        $command = $this->createStub(Command::class);
        $complexResponse = [
            'id' => 123,
            'status' => 'completed',
            'results' => ['item1', 'item2', 'item3'],
            'metadata' => [
                'timestamp' => '2024-01-01T10:00:00Z',
            ],
        ];

        $envelope = new Envelope($command);
        $envelope = $envelope->with(new \Symfony\Component\Messenger\Stamp\HandledStamp($complexResponse, 'handler'));

        $this->messageBus
            ->method('dispatch')
            ->willReturn($envelope);

        $this->logger->method('start');
        $this->logger->method('success');

        $result = $this->commandBus->dispatch($command);

        $this->assertSame($complexResponse, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itHandlesExceptionChainCorrectly(): void
    {
        $command = $this->createStub(Command::class);
        $deepException = new \InvalidArgumentException('Deep error');
        $middleException = new \LogicException('Middle error', 0, $deepException);

        $envelope = new Envelope($command);
        $handlerFailedException = new HandlerFailedException($envelope, [$middleException]);

        $this->messageBus
            ->method('dispatch')
            ->willThrowException($handlerFailedException);

        $this->logger->method('start');
        $this->logger->method('error');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Deep error');

        $this->commandBus->dispatch($command);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itPreservesCommandObjectIdentityThroughDispatch(): void
    {
        $command = new class() implements Command {
            public string $data = 'test-data';
        };

        $response = 'success';

        $envelope = new Envelope($command);
        $envelope = $envelope->with(new \Symfony\Component\Messenger\Stamp\HandledStamp($response, 'handler'));

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->identicalTo($command))
            ->willReturn($envelope);

        $this->logger->method('start');
        $this->logger->method('success');

        $result = $this->commandBus->dispatch($command);

        $this->assertSame($response, $result);
    }
}
