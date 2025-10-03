<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandHandler;
use BoutDeCode\ETLCoreBundle\Run\Domain\Runner\PipelineRunner;
use BoutDeCode\ETLCoreBundle\Run\Domain\Specification\IsPipelineExecutableSpecification;
use Webmozart\Assert\Assert;

final readonly class ExecuteWorkflowCommandHandler implements CommandHandler
{
    public function __construct(
        private PipelineProvider $pipelineProvider,
        private PipelineRunner $pipelineRunner,
        private IsPipelineExecutableSpecification $isPipelineExecutableSpecification
    ) {
    }

    public function __invoke(ExecuteWorkflowCommand $command): Context
    {
        $pipeline = $this->pipelineProvider->findPipelineByIdentifier($command->pipelineId);

        Assert::isInstanceOf($pipeline, Pipeline::class);

        if (! $this->isPipelineExecutableSpecification->isSatisfiedBy($pipeline)) {
            return Context::fromNoExecution();
        }

        return $this->pipelineRunner->run(
            $pipeline,
        );
    }
}
