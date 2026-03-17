<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Domain\Attribute;

use BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute\AsExecutableStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AsExecutableStepTest extends TestCase
{
    #[Test]
    public function itIsAPhpAttribute(): void
    {
        $refClass = new \ReflectionClass(AsExecutableStep::class);
        $attributes = $refClass->getAttributes(\Attribute::class);

        $this->assertNotEmpty($attributes, 'AsExecutableStep must carry the #[Attribute] meta-attribute');

        /** @var \Attribute $attribute */
        $attribute = $attributes[0]->newInstance();
        $this->assertSame(\Attribute::TARGET_CLASS, $attribute->flags);
    }

    #[Test]
    public function itStoresCode(): void
    {
        $attribute = new AsExecutableStep(code: 'etl.test.my_step');

        $this->assertSame('etl.test.my_step', $attribute->code);
    }

    #[Test]
    public function itDefaultsToEmptyConfigurationDescription(): void
    {
        $attribute = new AsExecutableStep(code: 'etl.test.my_step');

        $this->assertSame([], $attribute->configurationDescription);
    }

    #[Test]
    public function itStoresConfigurationDescription(): void
    {
        $description = [
            'source' => 'Path to the source file',
            'delimiter' => 'Field delimiter',
        ];

        $attribute = new AsExecutableStep(
            code: 'etl.test.my_step',
            configurationDescription: $description,
        );

        $this->assertSame($description, $attribute->configurationDescription);
    }

    #[Test]
    public function codeIsReadonly(): void
    {
        $refClass = new \ReflectionClass(AsExecutableStep::class);
        $property = $refClass->getProperty('code');

        $this->assertTrue($property->isReadOnly());
    }

    #[Test]
    public function configurationDescriptionIsReadonly(): void
    {
        $refClass = new \ReflectionClass(AsExecutableStep::class);
        $property = $refClass->getProperty('configurationDescription');

        $this->assertTrue($property->isReadOnly());
    }

    #[Test]
    public function itCanBeReadFromAClass(): void
    {
        $refClass = new \ReflectionClass(StepWithAttribute::class);
        $attributes = $refClass->getAttributes(AsExecutableStep::class);

        $this->assertCount(1, $attributes);

        /** @var AsExecutableStep $instance */
        $instance = $attributes[0]->newInstance();

        $this->assertSame('etl.test.step_with_attribute', $instance->code);
        $this->assertSame([
            'foo' => 'bar description',
        ], $instance->configurationDescription);
    }
}

// ---------------------------------------------------------------------------
// Fixture class

#[AsExecutableStep(
    code: 'etl.test.step_with_attribute',
    configurationDescription: [
        'foo' => 'bar description',
    ],
)]
class StepWithAttribute
{
}
