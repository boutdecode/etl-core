<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Domain\Resolver;

use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\ExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Resolver\DefaultStepResolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DefaultStepResolverTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructWithoutStepsShouldCreateEmptyResolver(): void
    {
        $resolver = new DefaultStepResolver();

        $result = $resolver->resolve('any.code');

        $this->assertNull($result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructWithStepsShouldAddAllSteps(): void
    {
        $step1 = $this->createMock(ExecutableStep::class);
        $step1->method('getCode')->willReturn('step1.code');

        $step2 = $this->createMock(ExecutableStep::class);
        $step2->method('getCode')->willReturn('step2.code');

        $steps = [$step1, $step2];
        $resolver = new DefaultStepResolver($steps);

        $this->assertSame($step1, $resolver->resolve('step1.code'));
        $this->assertSame($step2, $resolver->resolve('step2.code'));
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function addStepShouldAddStepToResolver(): void
    {
        $resolver = new DefaultStepResolver();

        $step = $this->createMock(ExecutableStep::class);
        $step->method('getCode')->willReturn('test.step.code');

        $resolver->addStep($step);

        $this->assertSame($step, $resolver->resolve('test.step.code'));
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function addMultipleStepsShouldAddAllSteps(): void
    {
        $resolver = new DefaultStepResolver();

        $step1 = $this->createMock(ExecutableStep::class);
        $step1->method('getCode')->willReturn('step1.code');

        $step2 = $this->createMock(ExecutableStep::class);
        $step2->method('getCode')->willReturn('step2.code');

        $step3 = $this->createMock(ExecutableStep::class);
        $step3->method('getCode')->willReturn('step3.code');

        $resolver->addStep($step1);
        $resolver->addStep($step2);
        $resolver->addStep($step3);

        $this->assertSame($step1, $resolver->resolve('step1.code'));
        $this->assertSame($step2, $resolver->resolve('step2.code'));
        $this->assertSame($step3, $resolver->resolve('step3.code'));
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function resolveShouldReturnNullForNonExistentStep(): void
    {
        $step = $this->createMock(ExecutableStep::class);
        $step->method('getCode')->willReturn('existing.code');

        $resolver = new DefaultStepResolver([$step]);

        $this->assertNull($resolver->resolve('non.existent.code'));
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function resolveShouldReturnFirstMatchingStepWhenMultipleStepsHaveSameCode(): void
    {
        $step1 = $this->createMock(ExecutableStep::class);
        $step1->method('getCode')->willReturn('same.code');

        $step2 = $this->createMock(ExecutableStep::class);
        $step2->method('getCode')->willReturn('same.code');

        $resolver = new DefaultStepResolver();
        $resolver->addStep($step1);
        $resolver->addStep($step2);

        // Should return the first one added
        $this->assertSame($step1, $resolver->resolve('same.code'));
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function resolveShouldBeCaseSensitive(): void
    {
        $step = $this->createMock(ExecutableStep::class);
        $step->method('getCode')->willReturn('CaseSensitive.Code');

        $resolver = new DefaultStepResolver([$step]);

        $this->assertSame($step, $resolver->resolve('CaseSensitive.Code'));
        $this->assertNull($resolver->resolve('casesensitive.code'));
        $this->assertNull($resolver->resolve('CASESENSITIVE.CODE'));
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function resolverShouldImplementInterface(): void
    {
        $resolver = new DefaultStepResolver();

        $this->assertInstanceOf(
            \BoutDeCode\ETLCoreBundle\ETL\Domain\Resolver\StepResolver::class,
            $resolver
        );
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructWithIterableStepsShouldWork(): void
    {
        $step1 = $this->createMock(ExecutableStep::class);
        $step1->method('getCode')->willReturn('step1.code');

        $step2 = $this->createMock(ExecutableStep::class);
        $step2->method('getCode')->willReturn('step2.code');

        // Using ArrayIterator to test iterable
        $steps = new \ArrayIterator([$step1, $step2]);
        $resolver = new DefaultStepResolver($steps);

        $this->assertSame($step1, $resolver->resolve('step1.code'));
        $this->assertSame($step2, $resolver->resolve('step2.code'));
    }
}
