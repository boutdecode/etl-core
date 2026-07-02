<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\PipelinePersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandHandler;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Persister\PipelineHistoryPersister;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Persister\StepHistoryPersister;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Provider\PipelineHistoryProvider;
use Webmozart\Assert\Assert;

final readonly class PurgePipelineCommandHandler implements CommandHandler
{
    public function __construct(
        private PipelineProvider $pipelineProvider,
        private PipelineHistoryProvider $pipelineHistoryProvider,
        private PipelineHistoryPersister $pipelineHistoryPersister,
        private StepHistoryPersister $stepHistoryPersister,
        private PipelinePersister $pipelinePersister,
    ) {
    }

    public function __invoke(PurgePipelineCommand $command): void
    {
        $pipeline = $this->pipelineProvider->findPipelineByIdentifier($command->pipelineId);

        Assert::isInstanceOf($pipeline, Pipeline::class);

        foreach ($this->pipelineHistoryProvider->findByPipeline($pipeline) as $pipelineHistory) {
            foreach ($pipelineHistory->getStepHistories() as $stepHistory) {
                $this->stepHistoryPersister->delete($stepHistory);
            }

            $this->pipelineHistoryPersister->delete($pipelineHistory);
        }

        $this->pipelinePersister->delete($pipeline);
    }
}
