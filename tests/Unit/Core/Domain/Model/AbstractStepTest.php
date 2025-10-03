<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Core\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\AbstractStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractStepTest extends TestCase
{
    private TestStep $step;

    protected function setUp(): void
    {
        $this->step = new TestStep();
    }

    #[Test]
    public function getNameShouldReturnInitializedName(): void
    {
        $this->assertSame('Test Step', $this->step->getName());
    }

    #[Test]
    public function getCodeShouldReturnInitializedCode(): void
    {
        $this->assertSame('test.step.code', $this->step->getCode());
    }

    #[Test]
    public function getConfigurationShouldReturnInitializedConfiguration(): void
    {
        $expected = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];
        $this->assertSame($expected, $this->step->getConfiguration());
    }

    #[Test]
    public function processShouldReturnContextUnchanged(): void
    {
        $context = new Context('test input');

        $result = $this->step->process($context);

        $this->assertSame($context, $result);
    }

    #[Test]
    public function setNameShouldUpdateName(): void
    {
        $newName = 'Updated Step Name';
        $this->step->setName($newName);

        $this->assertSame($newName, $this->step->getName());
    }

    #[Test]
    public function setNameWithNullShouldSetToNull(): void
    {
        $this->step->setName(null);

        $this->assertSame($this->step->getCode(), $this->step->getName());
    }

    #[Test]
    public function setCodeShouldUpdateCode(): void
    {
        $newCode = 'new.step.code';
        $this->step->setCode($newCode);

        $this->assertSame($newCode, $this->step->getCode());
    }

    #[Test]
    public function setConfigurationShouldUpdateConfiguration(): void
    {
        $newConfig = [
            'newKey1' => 'newValue1',
            'newKey2' => 'newValue2',
        ];
        $this->step->setConfiguration($newConfig);

        $this->assertSame($newConfig, $this->step->getConfiguration());
    }

    #[Test]
    public function getOrderShouldReturnInitializedOrder(): void
    {
        $this->assertSame(5, $this->step->getOrder());
    }

    #[Test]
    public function emptyConfigurationShouldWork(): void
    {
        $emptyStep = new EmptyTestStep();

        $this->assertSame('empty.code', $emptyStep->getName());
        $this->assertSame('empty.code', $emptyStep->getCode());
        $this->assertSame([], $emptyStep->getConfiguration());
        $this->assertSame(0, $emptyStep->getOrder());
    }
}

// Test class extending AbstractStep for testing purposes
class TestStep extends AbstractStep
{
    public function __construct()
    {
        $this->name = 'Test Step';
        $this->code = 'test.step.code';
        $this->configuration = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];
        $this->order = 5;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }
}

// Test class with empty configuration
class EmptyTestStep extends AbstractStep
{
    public function __construct()
    {
        $this->name = null;
        $this->code = 'empty.code';
        $this->configuration = [];
    }
}
