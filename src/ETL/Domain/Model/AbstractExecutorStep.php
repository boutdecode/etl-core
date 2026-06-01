<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\Trait\ExecutableStepAttributeTrait;

abstract class AbstractExecutorStep implements ExecutorStep
{
    use ExecutableStepAttributeTrait;

    public function process(Context $context): Context
    {
        $stepConfig = $context->getConfigurationValue($this->getName(), []);
        /** @var array<string, mixed> $config */
        $config = is_array($stepConfig) ? $stepConfig : [];

        $command = $config['command'] ?? $this->configuration['command'] ?? '';
        $commandStr = is_string($command) ? $command : '';

        $arguments = $config['arguments'] ?? $this->configuration['arguments'] ?? [];
        /** @var array<string, mixed> $argumentsArr */
        $argumentsArr = is_array($arguments) ? $arguments : [];

        $result = $this->execute($commandStr, $argumentsArr, $context, $config);

        return $context->setResult($this->getName(), $result);
    }
}
