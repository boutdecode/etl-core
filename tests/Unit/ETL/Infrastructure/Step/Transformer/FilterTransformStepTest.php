<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Infrastructure\Step\Transformer;

use BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Transformer\FilterTransformStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FilterTransformStepTest extends TestCase
{
    private FilterTransformStep $transformStep;

    protected function setUp(): void
    {
        $this->transformStep = new FilterTransformStep();
    }

    #[Test]
    public function getCodeShouldReturnCorrectCode(): void
    {
        $this->assertSame(FilterTransformStep::CODE, $this->transformStep->getCode());
        $this->assertSame('etl.transformer.filter', $this->transformStep->getCode());
    }

    #[Test]
    public function transformWithDefaultFilterShouldReturnAllItems(): void
    {
        $data = [
            [
                'name' => 'John',
                'age' => 30,
            ],
            [
                'name' => 'Jane',
                'age' => 25,
            ],
            [
                'name' => 'Bob',
                'age' => 35,
            ],
        ];

        $result = $this->transformStep->transform($data);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertSame($data, array_values($result)); // array_values to reset keys
    }

    #[Test]
    public function transformWithSimpleFilterExpressionShouldFilterData(): void
    {
        $data = [
            [
                'name' => 'John',
                'age' => 30,
            ],
            [
                'name' => 'Jane',
                'age' => 25,
            ],
            [
                'name' => 'Bob',
                'age' => 35,
            ],
        ];

        $configuration = [
            'filterExpression' => 'item.age > 27',
        ];
        $result = $this->transformStep->transform($data, $configuration);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $resultValues = array_values($result);
        $this->assertSame([
            'name' => 'John',
            'age' => 30,
        ], $resultValues[0]);
        $this->assertSame([
            'name' => 'Bob',
            'age' => 35,
        ], $resultValues[1]);
    }

    #[Test]
    public function transformWithStringFilterShouldWork(): void
    {
        $data = [
            [
                'name' => 'John Doe',
                'category' => 'admin',
            ],
            [
                'name' => 'Jane Smith',
                'category' => 'user',
            ],
            [
                'name' => 'Bob Johnson',
                'category' => 'admin',
            ],
        ];

        $configuration = [
            'filterExpression' => 'item.category == "admin"',
        ];
        $result = $this->transformStep->transform($data, $configuration);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $resultValues = array_values($result);
        $this->assertSame([
            'name' => 'John Doe',
            'category' => 'admin',
        ], $resultValues[0]);
        $this->assertSame([
            'name' => 'Bob Johnson',
            'category' => 'admin',
        ], $resultValues[1]);
    }

    #[Test]
    public function transformWithComplexFilterShouldWork(): void
    {
        $data = [
            [
                'name' => 'John',
                'age' => 30,
                'active' => true,
            ],
            [
                'name' => 'Jane',
                'age' => 25,
                'active' => false,
            ],
            [
                'name' => 'Bob',
                'age' => 35,
                'active' => true,
            ],
            [
                'name' => 'Alice',
                'age' => 28,
                'active' => false,
            ],
        ];

        $configuration = [
            'filterExpression' => 'item.age >= 30 and item.active == true',
        ];
        $result = $this->transformStep->transform($data, $configuration);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $resultValues = array_values($result);
        $this->assertSame([
            'name' => 'John',
            'age' => 30,
            'active' => true,
        ], $resultValues[0]);
        $this->assertSame([
            'name' => 'Bob',
            'age' => 35,
            'active' => true,
        ], $resultValues[1]);
    }

    #[Test]
    public function transformWithEmptyDataShouldReturnEmptyArray(): void
    {
        $result = $this->transformStep->transform([]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function transformShouldThrowExceptionForNonArrayData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data must be an array');

        $this->transformStep->transform('not an array');
    }

    #[Test]
    public function transformWithNonArrayDataIntegerShouldThrowException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data must be an array');

        $this->transformStep->transform(123);
    }

    #[Test]
    public function constructWithCustomFilterExpressionShouldSetDefault(): void
    {
        $customFilter = 'item.status == "active"';
        $transformStep = new FilterTransformStep($customFilter);

        $data = [
            [
                'name' => 'John',
                'status' => 'active',
            ],
            [
                'name' => 'Jane',
                'status' => 'inactive',
            ],
        ];

        $result = $transformStep->transform($data);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    #[Test]
    public function configurationParameterShouldOverrideConstructorDefault(): void
    {
        $constructorFilter = 'item.age > 50';
        $transformStep = new FilterTransformStep($constructorFilter);

        $data = [
            [
                'name' => 'John',
                'age' => 30,
            ],
            [
                'name' => 'Jane',
                'age' => 25,
            ],
        ];

        $configuration = [
            'filterExpression' => 'item.age < 30',
        ];
        $result = $transformStep->transform($data, $configuration);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $resultValues = array_values($result);
        $this->assertSame([
            'name' => 'Jane',
            'age' => 25,
        ], $resultValues[0]);
    }

    #[Test]
    public function transformWithInvalidExpressionShouldThrowException(): void
    {
        $this->expectException(\Symfony\Component\ExpressionLanguage\SyntaxError::class);

        $data = [[
            'name' => 'John',
        ]];
        $configuration = [
            'filterExpression' => 'invalid syntax here !!!',
        ];

        $this->transformStep->transform($data, $configuration);
    }

    #[Test]
    public function stepShouldImplementCorrectInterface(): void
    {
        $this->assertInstanceOf(
            \BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractTransformerStep::class,
            $this->transformStep
        );
    }

    #[Test]
    public function transformWithFilterExpressionFromConfigurationPropertyShouldWork(): void
    {
        // Test that the step can also read from internal configuration
        $data = [
            [
                'score' => 85,
            ],
            [
                'score' => 75,
            ],
            [
                'score' => 95,
            ],
        ];

        $configuration = [
            'filterExpression' => 'item.score >= 80',
        ];
        $result = $this->transformStep->transform($data, $configuration);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $resultValues = array_values($result);
        $this->assertSame([
            'score' => 85,
        ], $resultValues[0]);
        $this->assertSame([
            'score' => 95,
        ], $resultValues[1]);
    }
}
