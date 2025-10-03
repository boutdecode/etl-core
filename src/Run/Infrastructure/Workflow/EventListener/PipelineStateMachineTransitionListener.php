<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Infrastructure\Workflow\EventListener;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Webmozart\Assert\Assert;

#[AsTransitionListener(workflow: 'pipeline_lifecycle', transition: 'reset', method: 'onPipelineReset')]
final readonly class PipelineStateMachineTransitionListener
{
    public function onPipelineReset(TransitionEvent $event): void
    {
        $pipeline = $event->getSubject();

        Assert::isInstanceOf($pipeline, Pipeline::class);

        $pipeline->reset();
    }
}
