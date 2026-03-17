<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\Trait\ExecutableStepAttributeTrait;

abstract class AbstractTransformerStep implements TransformerStep
{
    use ExecutableStepAttributeTrait;

    public function process(Context $context): Context
    {
        $stepConfig = $context->getConfigurationValue($this->getName(), []);
        /** @var array<string, mixed> $config */
        $config = is_array($stepConfig) ? $stepConfig : [];

        $result = $this->transform($context->getInput(), $context, $config);

        return $context->setResult($this->getName(), $result);
    }
}
