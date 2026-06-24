<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Statistics\Application\Operation\Query;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\WorkflowProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Statistics\Application\Operation\Query\GetWorkflowStatisticQuery;
use BoutDeCode\ETLCoreBundle\Statistics\Application\Operation\Query\GetWorkflowStatisticQueryHandler;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Provider\WorkflowStatisticProvider;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\WorkflowStatistic;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GetWorkflowStatisticQueryHandlerTest extends TestCase
{
    private WorkflowProvider $workflowProvider;

    private WorkflowStatisticProvider $statisticProvider;

    private GetWorkflowStatisticQueryHandler $handler;

    protected function setUp(): void
    {
        $this->workflowProvider = $this->createMock(WorkflowProvider::class);
        $this->statisticProvider = $this->createMock(WorkflowStatisticProvider::class);
        $this->handler = new GetWorkflowStatisticQueryHandler(
            $this->workflowProvider,
            $this->statisticProvider,
        );
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function invokeShouldReturnStatisticWhenWorkflowAndStatisticExist(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $statistic = $this->createMock(WorkflowStatistic::class);

        $this->workflowProvider->method('findWorkflowByIdentifier')
            ->with('workflow-1')
            ->willReturn($workflow);

        $this->statisticProvider->method('findByWorkflow')
            ->with($workflow)
            ->willReturn($statistic);

        $result = ($this->handler)(new GetWorkflowStatisticQuery('workflow-1'));

        $this->assertSame($statistic, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function invokeShouldReturnNullWhenWorkflowNotFound(): void
    {
        $this->workflowProvider->method('findWorkflowByIdentifier')->willReturn(null);
        $this->statisticProvider->expects($this->never())->method('findByWorkflow');

        $result = ($this->handler)(new GetWorkflowStatisticQuery('unknown'));

        $this->assertNull($result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function invokeShouldReturnNullWhenNoStatisticYet(): void
    {
        $workflow = $this->createMock(Workflow::class);

        $this->workflowProvider->method('findWorkflowByIdentifier')->willReturn($workflow);
        $this->statisticProvider->method('findByWorkflow')->willReturn(null);

        $result = ($this->handler)(new GetWorkflowStatisticQuery('workflow-1'));

        $this->assertNull($result);
    }
}
