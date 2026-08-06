<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Exception;

final class InvalidStepConfigurationException extends \InvalidArgumentException
{
    public static function missingField(string $stepCode, string $field): self
    {
        return new self(sprintf(
            'Step "%s": missing required configuration field "%s".',
            $stepCode,
            $field,
        ));
    }

    /**
     * @param string[] $expectedTypes
     */
    public static function invalidType(string $stepCode, string $field, array $expectedTypes, mixed $value): self
    {
        return new self(sprintf(
            'Step "%s": configuration field "%s" must be of type "%s", "%s" given.',
            $stepCode,
            $field,
            implode('|', $expectedTypes),
            get_debug_type($value),
        ));
    }

    /**
     * @param array<mixed, mixed> $allowedValues
     */
    public static function invalidEnum(string $stepCode, string $field, array $allowedValues, mixed $value): self
    {
        return new self(sprintf(
            'Step "%s": configuration field "%s" must be one of %s, %s given.',
            $stepCode,
            $field,
            json_encode($allowedValues) ?: '[]',
            json_encode($value) ?: 'null',
        ));
    }
}
