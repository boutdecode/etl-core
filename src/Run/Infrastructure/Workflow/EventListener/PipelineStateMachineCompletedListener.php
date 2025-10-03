<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Infrastructure\Workflow\EventListener;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\PipelinePersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use Symfony\Component\Workflow\Attribute\AsCompletedListener;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Webmozart\Assert\Assert;

#[AsCompletedListener(workflow: 'pipeline_lifecycle')]
final readonly class PipelineStateMachineCompletedListener
{
    public function __construct(
        private PipelinePersister $pipelinePersister
    ) {
    }

    public function __invoke(CompletedEvent $event): void
    {
        $pipeline = $event->getSubject();

        Assert::isInstanceOf($pipeline, Pipeline::class);

        $this->pipelinePersister->save($pipeline);
    }
}
