<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Infrastructure\Step\Transformer;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractTransformerStep;
use BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Transformer\DataMappingTransformStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DataMappingTransformStepTest extends TestCase
{
    private DataMappingTransformStep $transformStep;

    protected function setUp(): void
    {
        $this->transformStep = new DataMappingTransformStep();
    }

    #[Test]
    public function getCodeShouldReturnCorrectCode(): void
    {
        $this->assertSame('etl.transformer.data_mapping', $this->transformStep->getCode());
    }

    #[Test]
    public function stepShouldExtendAbstractTransformerStep(): void
    {
        $this->assertInstanceOf(AbstractTransformerStep::class, $this->transformStep);
    }

    #[Test]
    public function getConfigurationDescriptionShouldReturnExpectedKeys(): void
    {
        $description = $this->transformStep->getConfigurationDescription();

        $this->assertIsArray($description);
        $this->assertArrayHasKey('fieldMapping', $description);
        $this->assertArrayHasKey('removeUnmappedFields', $description);
    }

    #[Test]
    public function transformWithNoMappingShouldReturnDataAsIs(): void
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
        ];

        $result = $this->transformStep->transform($data, new Context(null));

        $this->assertSame($data, $result);
    }

    #[Test]
    public function transformWithEmptyMappingShouldReturnDataAsIs(): void
    {
        $data = [
            'name' => 'John',
            'age' => 30,
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [],
        ]);

        $this->assertSame($data, $result);
    }

    #[Test]
    public function transformWithNonArrayDataAndNoMappingShouldReturnDataAsIs(): void
    {
        $result = $this->transformStep->transform('just a string', new Context(null));

        $this->assertSame('just a string', $result);
    }

    #[Test]
    public function transformSingleRecordShouldRenameFields(): void
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'first_name' => 'firstName',
                'last_name' => 'lastName',
            ],
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('firstName', $result);
        $this->assertArrayHasKey('lastName', $result);
        $this->assertSame('John', $result['firstName']);
        $this->assertSame('Doe', $result['lastName']);
    }

    #[Test]
    public function transformMultipleRecordsShouldRenameFieldsOnEach(): void
    {
        $data = [
            [
                'old_name' => 'John',
            ],
            [
                'old_name' => 'Jane',
            ],
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'old_name' => 'name',
            ],
        ]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame('John', $result[0]['name']);
        $this->assertSame('Jane', $result[1]['name']);
    }

    #[Test]
    public function transformWithRemoveUnmappedFieldsTrueShouldDropOtherFields(): void
    {
        $data = [
            'first_name' => 'John',
            'age' => 30,
            'city' => 'Paris',
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'first_name' => 'name',
            ],
            'removeUnmappedFields' => true,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('age', $result);
        $this->assertArrayNotHasKey('city', $result);
    }

    #[Test]
    public function transformWithRemoveUnmappedFieldsFalseShouldKeepOtherFields(): void
    {
        $data = [
            'first_name' => 'John',
            'age' => 30,
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'first_name' => 'name',
            ],
            'removeUnmappedFields' => false,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('age', $result);
    }

    #[Test]
    public function transformWithConstructorMappingShouldBeUsedByDefault(): void
    {
        $transformStep = new DataMappingTransformStep(
            fieldMapping: [
                'src' => 'dst',
            ],
            removeUnmappedFields: true,
        );

        $data = [
            'src' => 'value',
            'extra' => 'ignored',
        ];

        $result = $transformStep->transform($data, new Context(null));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('dst', $result);
        $this->assertArrayNotHasKey('extra', $result);
    }

    #[Test]
    public function transformConfigurationMappingShouldOverrideConstructorMapping(): void
    {
        $transformStep = new DataMappingTransformStep(
            fieldMapping: [
                'old' => 'new',
            ],
        );

        $data = [
            'override_src' => 'hello',
        ];

        $result = $transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'override_src' => 'override_dst',
            ],
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('override_dst', $result);
    }

    #[Test]
    public function transformWithComplexMappingUpperTransformShouldApply(): void
    {
        $data = [
            'name' => 'john',
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'name' => [
                    'target' => 'fullName',
                    'transform' => 'upper',
                ],
            ],
        ]);

        $this->assertIsArray($result);
        $this->assertSame('JOHN', $result['fullName']);
    }

    #[Test]
    public function transformWithComplexMappingLowerTransformShouldApply(): void
    {
        $data = [
            'name' => 'JOHN',
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'name' => [
                    'target' => 'lowerName',
                    'transform' => 'lower',
                ],
            ],
        ]);

        $this->assertSame('john', $result['lowerName']);
    }

    #[Test]
    public function transformWithComplexMappingTrimTransformShouldApply(): void
    {
        $data = [
            'name' => '  John  ',
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'name' => [
                    'target' => 'trimmedName',
                    'transform' => 'trim',
                ],
            ],
        ]);

        $this->assertSame('John', $result['trimmedName']);
    }

    #[Test]
    public function transformWithComplexMappingIntTransformShouldCast(): void
    {
        $data = [
            'score' => '42',
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'score' => [
                    'target' => 'intScore',
                    'transform' => 'int',
                ],
            ],
        ]);

        $this->assertSame(42, $result['intScore']);
    }

    #[Test]
    public function transformWithComplexMappingFloatTransformShouldCast(): void
    {
        $data = [
            'price' => '9.99',
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'price' => [
                    'target' => 'floatPrice',
                    'transform' => 'float',
                ],
            ],
        ]);

        $this->assertSame(9.99, $result['floatPrice']);
    }

    #[Test]
    public function transformWithComplexMappingStringTransformShouldCast(): void
    {
        $data = [
            'count' => 42,
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'count' => [
                    'target' => 'strCount',
                    'transform' => 'string',
                ],
            ],
        ]);

        $this->assertSame('42', $result['strCount']);
    }

    #[Test]
    public function transformWithComplexMappingBoolTransformShouldCast(): void
    {
        $data = [
            'active' => 1,
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'active' => [
                    'target' => 'boolActive',
                    'transform' => 'bool',
                ],
            ],
        ]);

        $this->assertTrue($result['boolActive']);
    }

    #[Test]
    public function transformWithComplexMappingDateTransformShouldReturnDateTimeImmutable(): void
    {
        $data = [
            'birthday' => '1990-06-15',
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'birthday' => [
                    'target' => 'parsedDate',
                    'transform' => 'date',
                ],
            ],
        ]);

        $this->assertInstanceOf(\DateTimeImmutable::class, $result['parsedDate']);
        $this->assertSame('1990-06-15', $result['parsedDate']->format('Y-m-d'));
    }

    #[Test]
    public function transformWithComplexMappingDateFromTimestampShouldWork(): void
    {
        $timestamp = mktime(0, 0, 0, 1, 1, 2000);
        $data = [
            'ts' => $timestamp,
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'ts' => [
                    'target' => 'parsedDate',
                    'transform' => 'date',
                ],
            ],
        ]);

        $this->assertInstanceOf(\DateTimeImmutable::class, $result['parsedDate']);
    }

    #[Test]
    public function transformWithComplexMappingDateFromDateTimeInterfaceShouldWork(): void
    {
        $dt = new \DateTime('2024-03-17');
        $data = [
            'created' => $dt,
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'created' => [
                    'target' => 'createdAt',
                    'transform' => 'date',
                ],
            ],
        ]);

        $this->assertInstanceOf(\DateTimeImmutable::class, $result['createdAt']);
        $this->assertSame('2024-03-17', $result['createdAt']->format('Y-m-d'));
    }

    #[Test]
    public function transformWithComplexMappingDateFromInvalidStringShouldReturnNull(): void
    {
        $data = [
            'bad_date' => 'not-a-date-at-all-xyz',
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'bad_date' => [
                    'target' => 'parsedDate',
                    'transform' => 'date',
                ],
            ],
        ]);

        $this->assertNull($result['parsedDate']);
    }

    #[Test]
    public function transformWithComplexMappingJsonDecodeShouldParseString(): void
    {
        $data = [
            'meta' => '{"key":"value"}',
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'meta' => [
                    'target' => 'decoded',
                    'transform' => 'json_decode',
                ],
            ],
        ]);

        $this->assertSame([
            'key' => 'value',
        ], $result['decoded']);
    }

    #[Test]
    public function transformWithComplexMappingJsonEncodeShouldReturnJsonString(): void
    {
        // 'id' is a non-array field, so the record is detected as a single record (not a list of records)
        $data = [
            'id' => 1,
            'meta' => [
                'key' => 'value',
            ],
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'meta' => [
                    'target' => 'encoded',
                    'transform' => 'json_encode',
                ],
            ],
        ]);

        $this->assertSame('{"key":"value"}', $result['encoded']);
    }

    #[Test]
    public function transformWithComplexMappingDefaultValueShouldBeUsedWhenFieldMissing(): void
    {
        $data = [
            'name' => 'John',
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'missing_field' => [
                    'target' => 'withDefault',
                    'default' => 'N/A',
                ],
            ],
        ]);

        $this->assertSame('N/A', $result['withDefault']);
    }

    #[Test]
    public function transformWithComplexMappingNoTargetShouldUseSourceFieldAsKey(): void
    {
        $data = [
            'name' => 'john',
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'name' => [
                    'transform' => 'upper',
                ],
            ],
        ]);

        $this->assertArrayHasKey('name', $result);
        $this->assertSame('JOHN', $result['name']);
    }

    #[Test]
    public function transformWithMissingSourceFieldAndNoDefaultShouldSkipEntry(): void
    {
        $data = [
            'other' => 'value',
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'missing' => 'target',
            ],
        ]);

        $this->assertArrayNotHasKey('target', $result);
        $this->assertArrayHasKey('other', $result);
    }

    #[Test]
    public function transformWithNonBoolRemoveUnmappedShouldFallBackToFalse(): void
    {
        $data = [
            'a' => 1,
            'b' => 2,
        ];

        $result = $this->transformStep->transform($data, new Context(null), [
            'fieldMapping' => [
                'a' => 'mapped_a',
            ],
            'removeUnmappedFields' => 'not_a_bool',
        ]);

        // non-bool → fallback to false → keep unmapped field 'b'
        $this->assertArrayHasKey('mapped_a', $result);
        $this->assertArrayHasKey('b', $result);
    }
}
