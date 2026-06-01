<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Infrastructure\Scheduler;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PlannedTaskProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\PlannedTask;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\Command;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\ExecutePlannedTaskCommand;
use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler\Messenger\SchedulePlannedTask;
use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler\Messenger\SchedulePlannedTaskHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Spy implementation of CommandBus that accepts stamps as optional
 * (the interface lacks a default but the concrete implementation has one).
 */
final class SpyCommandBusForSchedulePlannedTask implements CommandBus
{
    /**
     * @var list<Command>
     */
    public array $dispatched = [];

    public ?\Throwable $throwOnDispatch = null;

    public function dispatch(Command $command, array $stamps = []): mixed
    {
        if ($this->throwOnDispatch !== null) {
            throw $this->throwOnDispatch;
        }

        $this->dispatched[] = $command;

        return null;
    }
}

class SchedulePlannedTaskHandlerTest extends TestCase
{
    private SpyCommandBusForSchedulePlannedTask $commandBus;

    private PlannedTaskProvider $plannedTaskProvider;

    private SchedulePlannedTaskHandler $handler;

    protected function setUp(): void
    {
        $this->commandBus = new SpyCommandBusForSchedulePlannedTask();
        $this->plannedTaskProvider = $this->createMock(PlannedTaskProvider::class);
        $this->handler = new SchedulePlannedTaskHandler($this->plannedTaskProvider, $this->commandBus);
    }

    #[Test]
    public function itDispatchesNoCommandsWhenNoScheduledTasksExist(): void
    {
        $this->plannedTaskProvider
            ->expects($this->once())
            ->method('findScheduled')
            ->willReturn([]);

        ($this->handler)(new SchedulePlannedTask());

        $this->assertCount(0, $this->commandBus->dispatched);
    }

    #[Test]
    public function itDispatchesOneCommandForSingleScheduledTask(): void
    {
        $task = $this->createTaskWithId('task-abc');

        $this->plannedTaskProvider
            ->expects($this->once())
            ->method('findScheduled')
            ->willReturn([$task]);

        ($this->handler)(new SchedulePlannedTask());

        $this->assertCount(1, $this->commandBus->dispatched);
        $this->assertInstanceOf(ExecutePlannedTaskCommand::class, $this->commandBus->dispatched[0]);
        $this->assertSame('task-abc', $this->commandBus->dispatched[0]->plannedTaskId);
    }

    #[Test]
    public function itDispatchesOneCommandPerScheduledTask(): void
    {
        $tasks = [
            $this->createTaskWithId('task-1'),
            $this->createTaskWithId('task-2'),
            $this->createTaskWithId('task-3'),
        ];

        $this->plannedTaskProvider
            ->expects($this->once())
            ->method('findScheduled')
            ->willReturn($tasks);

        ($this->handler)(new SchedulePlannedTask());

        $this->assertCount(3, $this->commandBus->dispatched);

        foreach ($this->commandBus->dispatched as $command) {
            $this->assertInstanceOf(ExecutePlannedTaskCommand::class, $command);
        }
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itPreservesTaskIdInDispatchedCommands(): void
    {
        $taskIds = ['daily-import', 'hourly-sync', 'weekly-report'];
        $tasks = array_map([$this, 'createTaskWithId'], $taskIds);

        $this->plannedTaskProvider
            ->method('findScheduled')
            ->willReturn($tasks);

        ($this->handler)(new SchedulePlannedTask());

        $dispatchedIds = array_map(
            fn (Command $c) => $c instanceof ExecutePlannedTaskCommand ? $c->plannedTaskId : '',
            $this->commandBus->dispatched
        );

        $this->assertSame($taskIds, $dispatchedIds);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itPropagatesCommandBusExceptionWithoutCatching(): void
    {
        $task = $this->createTaskWithId('task-1');

        $this->plannedTaskProvider
            ->method('findScheduled')
            ->willReturn([$task]);

        $this->commandBus->throwOnDispatch = new \RuntimeException('Bus failure');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Bus failure');

        ($this->handler)(new SchedulePlannedTask());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itIsDecoratedAsMessageHandler(): void
    {
        $reflection = new \ReflectionClass(SchedulePlannedTaskHandler::class);
        $attributes = $reflection->getAttributes(\Symfony\Component\Messenger\Attribute\AsMessageHandler::class);

        $this->assertCount(1, $attributes);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itIsFinalAndReadonly(): void
    {
        $reflection = new \ReflectionClass(SchedulePlannedTaskHandler::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }

    private function createTaskWithId(string $id): PlannedTask
    {
        $task = $this->createMock(PlannedTask::class);
        $task->method('getId')->willReturn($id);

        return $task;
    }
}
