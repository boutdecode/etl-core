<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Domain\Model\Trait;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute\AsExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractExtractorStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExecutableStepAttributeTraitTest extends TestCase
{
    #[Test]
    public function getCodeReturnsCodeFromAttribute(): void
    {
        $step = new StepWithAttributeFixture();

        $this->assertSame('etl.test.trait_step', $step->getCode());
    }

    #[Test]
    public function getConfigurationDescriptionReturnsDescriptionFromAttribute(): void
    {
        $step = new StepWithAttributeFixture();

        $this->assertSame([
            'param_a' => 'Description of param A',
            'param_b' => 'Description of param B',
        ], $step->getConfigurationDescription());
    }

    #[Test]
    public function getCodeFallsBackToPropertyWhenNoAttribute(): void
    {
        $step = new StepWithoutAttributeFixture();

        $this->assertSame('etl.test.fallback', $step->getCode());
    }

    #[Test]
    public function getConfigurationDescriptionFallsBackToEmptyArrayWhenNoAttribute(): void
    {
        $step = new StepWithoutAttributeFixture();

        $this->assertSame([], $step->getConfigurationDescription());
    }

    #[Test]
    public function getNameReturnsCodeWhenNameNotSet(): void
    {
        $step = new StepWithAttributeFixture();

        $this->assertSame('etl.test.trait_step', $step->getName());
    }

    #[Test]
    public function getNameReturnsExplicitNameWhenSet(): void
    {
        $step = new StepWithAttributeFixture();
        $step->setName('My Custom Name');

        $this->assertSame('My Custom Name', $step->getName());
    }

    #[Test]
    public function setAndGetConfiguration(): void
    {
        $step = new StepWithAttributeFixture();
        $config = [
            'key' => 'value',
        ];
        $step->setConfiguration($config);

        $this->assertSame($config, $step->getConfiguration());
    }

    #[Test]
    public function setAndGetOrder(): void
    {
        $step = new StepWithAttributeFixture();
        $step->setOrder(42);

        $this->assertSame(42, $step->getOrder());
    }

    #[Test]
    public function defaultOrderIsZero(): void
    {
        $step = new StepWithAttributeFixture();

        $this->assertSame(0, $step->getOrder());
    }

    #[Test]
    public function defaultConfigurationIsEmpty(): void
    {
        $step = new StepWithAttributeFixture();

        $this->assertSame([], $step->getConfiguration());
    }
}

// ---------------------------------------------------------------------------
// Fixtures

#[AsExecutableStep(
    code: 'etl.test.trait_step',
    configurationDescription: [
        'param_a' => 'Description of param A',
        'param_b' => 'Description of param B',
    ],
)]
class StepWithAttributeFixture extends AbstractExtractorStep
{
    public function extract(mixed $source, Context $context, array $configuration = []): mixed
    {
        return $source;
    }
}

class StepWithoutAttributeFixture extends AbstractExtractorStep
{
    protected string $code = 'etl.test.fallback';

    public function extract(mixed $source, Context $context, array $configuration = []): mixed
    {
        return $source;
    }
}
