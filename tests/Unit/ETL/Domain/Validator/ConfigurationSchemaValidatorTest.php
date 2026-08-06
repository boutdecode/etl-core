<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Domain\Validator;

use BoutDeCode\ETLCoreBundle\ETL\Domain\Exception\InvalidStepConfigurationException;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Validator\ConfigurationSchemaValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ConfigurationSchemaValidatorTest extends TestCase
{
    private ConfigurationSchemaValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ConfigurationSchemaValidator();
    }

    #[Test]
    public function validatePassesForMatchingSimpleType(): void
    {
        $this->validator->validate('test.step', [
            'name' => 'John',
        ], [
            'name' => [
                'type' => 'string',
            ],
        ]);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function validateThrowsForMismatchingSimpleType(): void
    {
        $this->expectException(InvalidStepConfigurationException::class);
        $this->expectExceptionMessage('field "name" must be of type "string"');

        $this->validator->validate('test.step', [
            'name' => 123,
        ], [
            'name' => [
                'type' => 'string',
            ],
        ]);
    }

    #[Test]
    public function validatePassesForEitherTypeInUnion(): void
    {
        $this->validator->validate('test.step', [
            'value' => 42,
        ], [
            'value' => [
                'type' => 'string|number',
            ],
        ]);

        $this->validator->validate('test.step', [
            'value' => 'text',
        ], [
            'value' => [
                'type' => 'string|number',
            ],
        ]);

        $this->addToAssertionCount(2);
    }

    #[Test]
    public function validateThrowsWhenNoTypeInUnionMatches(): void
    {
        $this->expectException(InvalidStepConfigurationException::class);

        $this->validator->validate('test.step', [
            'value' => true,
        ], [
            'value' => [
                'type' => 'string|number',
            ],
        ]);
    }

    #[Test]
    public function validateIgnoresMissingOptionalField(): void
    {
        $this->validator->validate('test.step', [], [
            'name' => [
                'type' => 'string',
            ],
        ]);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function validateThrowsWhenRequiredFieldIsMissing(): void
    {
        $this->expectException(InvalidStepConfigurationException::class);
        $this->expectExceptionMessage('missing required configuration field "name"');

        $this->validator->validate('test.step', [], [
            'name' => [
                'type' => 'string',
                'required' => true,
            ],
        ]);
    }

    #[Test]
    public function validateIgnoresConfigurationKeysAbsentFromSchema(): void
    {
        $this->validator->validate('test.step', [
            'extra' => 'value',
        ], [
            'name' => [
                'type' => 'string',
            ],
        ]);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function validatePassesForNestedArrayProperties(): void
    {
        $this->validator->validate('test.step', [
            'items' => [
                [
                    'name' => 'a',
                ],
                [
                    'name' => 'b',
                ],
            ],
        ], [
            'items' => [
                'type' => 'array',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'required' => true,
                    ],
                ],
            ],
        ]);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function validateThrowsForInvalidNestedArrayItemProperty(): void
    {
        $this->expectException(InvalidStepConfigurationException::class);

        $this->validator->validate('test.step', [
            'items' => [
                [
                    'name' => 123,
                ],
            ],
        ], [
            'items' => [
                'type' => 'array',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function validatePassesForNestedObjectSchema(): void
    {
        $this->validator->validate('test.step', [
            'config' => [
                'enabled' => true,
            ],
        ], [
            'config' => [
                'type' => 'object',
                'schema' => [
                    'enabled' => [
                        'type' => 'boolean',
                    ],
                ],
            ],
        ]);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function validateThrowsForInvalidNestedObjectField(): void
    {
        $this->expectException(InvalidStepConfigurationException::class);

        $this->validator->validate('test.step', [
            'config' => [
                'enabled' => 'not_a_bool',
            ],
        ], [
            'config' => [
                'type' => 'object',
                'schema' => [
                    'enabled' => [
                        'type' => 'boolean',
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function validatePassesForAllowedEnumValue(): void
    {
        $this->validator->validate('test.step', [
            'mode' => 'append',
        ], [
            'mode' => [
                'type' => 'string',
                'enum' => ['append', 'overwrite'],
            ],
        ]);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function validateThrowsForDisallowedEnumValue(): void
    {
        $this->expectException(InvalidStepConfigurationException::class);
        $this->expectExceptionMessage('field "mode" must be one of ["append","overwrite"]');

        $this->validator->validate('test.step', [
            'mode' => 'delete',
        ], [
            'mode' => [
                'type' => 'string',
                'enum' => ['append', 'overwrite'],
            ],
        ]);
    }

    #[Test]
    public function validateEnumUsesStrictComparison(): void
    {
        $this->expectException(InvalidStepConfigurationException::class);

        $this->validator->validate('test.step', [
            'flag' => '1',
        ], [
            'flag' => [
                'enum' => [1, true],
            ],
        ]);
    }

    #[Test]
    public function validateEnumWithoutTypeStillChecksAllowedValues(): void
    {
        $this->validator->validate('test.step', [
            'level' => 2,
        ], [
            'level' => [
                'enum' => [1, 2, 3],
            ],
        ]);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function validateAcceptsEmptyArrayForArrayOrObjectType(): void
    {
        $this->validator->validate('test.step', [
            'list' => [],
            'map' => [],
        ], [
            'list' => [
                'type' => 'array',
            ],
            'map' => [
                'type' => 'object',
            ],
        ]);

        $this->addToAssertionCount(1);
    }
}
