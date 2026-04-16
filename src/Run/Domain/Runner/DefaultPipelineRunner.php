<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Runner;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Resolver\StepResolver;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\PipelineMiddlewareChain as PipelineMiddlewareChainInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class DefaultPipelineRunner implements PipelineRunner
{
    public function __construct(
        private PipelineMiddlewareChainInterface $middlewareChain,
        private StepResolver $stepResolver,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function run(Pipeline $pipeline): Context
    {
        /** @var list<Step> $executableSteps */
        $executableSteps = array_reduce(
            iterator_to_array($pipeline->getSteps()),
            function (array $carry, Step $step) use ($pipeline) {
                $executableStep = $this->stepResolver->resolve($step->getCode());
                if ($executableStep !== null) {
                    $executableStep = clone $executableStep;
                    $executableStep->setName($step->getName() ?? $step->getCode());
                    $executableStep->setConfiguration($step->getConfiguration());
                    $executableStep->setOrder($step->getOrder());

                    $carry[] = $executableStep;
                } else {
                    $this->logger->warning('ETL pipeline step not found: unknown step code "{code}" will be skipped. Check for a typo in the pipeline configuration.', [
                        'code' => $step->getCode(),
                        'pipeline' => $pipeline->getId(),
                    ]);
                }

                return $carry;
            },
            []
        );

        $pipeline->setRunnableSteps($executableSteps);

        $context = new Context($pipeline->getInput(), [], [], $this->prepareOverrideConfiguration($pipeline));
        $context->setPipeline($pipeline);

        return $this->middlewareChain->run($context, function (Context $context) {
            return $context;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareOverrideConfiguration(Pipeline $pipeline): array
    {
        $overrideConfig = [];
        foreach ($pipeline->getConfiguration() as $value) {
            if (! is_array($value)) {
                continue;
            }
            $key = isset($value['name']) && is_string($value['name']) ? $value['name']
                : (isset($value['code']) && is_string($value['code']) ? $value['code'] : 'unknown');
            $configuration = isset($value['configuration']) && is_array($value['configuration'])
                ? $value['configuration']
                : [];
            $overrideConfig[$key] = $configuration;
        }

        return $overrideConfig;
    }
}
