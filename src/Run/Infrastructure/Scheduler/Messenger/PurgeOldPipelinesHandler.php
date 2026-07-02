<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler\Messenger;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\PurgePipelineCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PurgeOldPipelinesHandler
{
    public function __construct(
        private PipelineProvider $pipelineProvider,
        private CommandBus $commandBus,
        private bool $purgeEnabled,
        private int $purgeRetentionDays,
    ) {
    }

    public function __invoke(PurgeOldPipelines $purgeOldPipelines): void
    {
        if (! $this->purgeEnabled) {
            return;
        }

        $threshold = (new \DateTimeImmutable())->modify(sprintf('-%d days', $this->purgeRetentionDays));

        foreach ($this->pipelineProvider->findPurgeablePipelines($threshold) as $pipeline) {
            $this->commandBus->dispatch(new PurgePipelineCommand($pipeline->getId()));
        }
    }
}
