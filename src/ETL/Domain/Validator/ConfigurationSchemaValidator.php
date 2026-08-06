<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Validator;

use BoutDeCode\ETLCoreBundle\ETL\Domain\Exception\InvalidStepConfigurationException;

/**
 * Validates a step configuration against the `configurationSchema` declared on
 * {@see \BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute\AsExecutableStep}.
 *
 * Schema DSL: `'field' => ['type' => 'string|number', 'required' => false]`.
 * - `type` accepts one or more of: string, number, integer|int, boolean|bool, array, object, null,
 *   separated by `|`. An unknown type keyword is permissive (never fails).
 * - `array` + `properties`: nested schema applied to each element of the list.
 * - `object` + `schema`: nested schema applied to the value itself.
 * - Fields missing from the configuration are ignored unless `required` is true.
 * - Configuration keys absent from the schema are not rejected (no `additionalProperties: false`).
 */
final class ConfigurationSchemaValidator
{
    /**
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $schema
     *
     * @throws InvalidStepConfigurationException
     */
    public function validate(string $stepCode, array $configuration, array $schema): void
    {
        $this->doValidate($stepCode, $configuration, $schema);
    }

    /**
     * Same as {@see validate()} but with widened array generics, so it can also be called recursively with
     * the array<mixed, mixed> shape produced by narrowing a `mixed` schema/configuration value through
     * `is_array()`.
     *
     * @param array<mixed, mixed> $configuration
     * @param array<mixed, mixed> $schema
     *
     * @throws InvalidStepConfigurationException
     */
    private function doValidate(string $stepCode, array $configuration, array $schema): void
    {
        foreach ($schema as $field => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            if (! array_key_exists($field, $configuration)) {
                if (($definition['required'] ?? false) === true) {
                    throw InvalidStepConfigurationException::missingField($stepCode, (string) $field);
                }

                continue;
            }

            $this->validateValue($stepCode, (string) $field, $configuration[$field], $definition);
        }
    }

    /**
     * @param array<mixed, mixed> $definition
     *
     * @throws InvalidStepConfigurationException
     */
    private function validateValue(string $stepCode, string $path, mixed $value, array $definition): void
    {
        $types = isset($definition['type']) && is_string($definition['type'])
            ? array_map('trim', explode('|', $definition['type']))
            : [];

        if ($types !== [] && ! $this->matchesAnyType($value, $types)) {
            throw InvalidStepConfigurationException::invalidType($stepCode, $path, $types, $value);
        }

        if (in_array('array', $types, true) && is_array($value) && isset($definition['properties']) && is_array($definition['properties'])) {
            $properties = $definition['properties'];
            foreach ($value as $index => $item) {
                if (! is_array($item)) {
                    throw InvalidStepConfigurationException::invalidType($stepCode, "{$path}[{$index}]", ['object'], $item);
                }

                $this->doValidate($stepCode, $item, $properties);
            }
        }

        if (in_array('object', $types, true) && is_array($value) && isset($definition['schema']) && is_array($definition['schema'])) {
            $nestedSchema = $definition['schema'];
            $this->doValidate($stepCode, $value, $nestedSchema);
        }
    }

    /**
     * @param string[] $types
     */
    private function matchesAnyType(mixed $value, array $types): bool
    {
        foreach ($types as $type) {
            if ($this->matchesType($value, $type)) {
                return true;
            }
        }

        return false;
    }

    private function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'number' => is_int($value) || is_float($value),
            'integer', 'int' => is_int($value),
            'boolean', 'bool' => is_bool($value),
            'array' => is_array($value) && ($value === [] || array_is_list($value)),
            'object' => is_array($value) && ($value === [] || ! array_is_list($value)),
            'null' => $value === null,
            default => true,
        };
    }
}
