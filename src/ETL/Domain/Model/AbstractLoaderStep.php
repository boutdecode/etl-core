<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\Trait\ExecutableStepAttributeTrait;

abstract class AbstractLoaderStep implements LoaderStep
{
    use ExecutableStepAttributeTrait;

    public function process(Context $context): Context
    {
        $stepConfig = $context->getConfigurationValue($this->getName(), []);
        /** @var array<string, mixed> $config */
        $config = is_array($stepConfig) ? $stepConfig : [];

        $destination = $config['destination'] ?? $this->configuration['destination'] ?? null;

        $result = $this->load($context->getInput(), $destination, $context, $config);

        return $context->setResult($this->getName(), $result);
    }
}
