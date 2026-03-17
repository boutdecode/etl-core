<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Transformer;

use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractTransformerStep;

class DataMappingTransformStep extends AbstractTransformerStep
{
    public const CODE = 'etl.transformer.data_mapping';

    protected string $code = self::CODE;

    public function __construct(
        /**
         * @var array<string, mixed>
         */
        private readonly array $fieldMapping = [],
        private readonly bool $removeUnmappedFields = false,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigurationDescription(): array
    {
        return [
            'fieldMapping' => 'An associative array defining how to map source fields to target fields. It can be a simple mapping (e.g., "old_name" => "new_name") or a complex mapping with transformations (e.g., "old_name" => ["target" => "new_name", "transform" => "upper", "default" => "N/A"])',
            'removeUnmappedFields' => 'Whether to remove fields that are not included in the field mapping (default: false)',
        ];
    }

    public function transform(mixed $data, array $configuration = []): mixed
    {
        $fieldMapping = $configuration['fieldMapping'] ?? $this->configuration['fieldMapping'] ?? $this->fieldMapping;
        $removeUnmapped = $configuration['removeUnmappedFields'] ?? $this->configuration['removeUnmappedFields'] ?? $this->removeUnmappedFields;

        if (! is_array($fieldMapping) || empty($fieldMapping)) {
            return $data; // No mapping defined, return data as-is
        }

        /** @var array<string, mixed> $fieldMapping */

        // Handle multiple records - check if it's an array where all values are arrays
        if (is_array($data) && ! empty($data)) {
            $isMultipleRecords = true;
            foreach ($data as $item) {
                if (! is_array($item)) {
                    $isMultipleRecords = false;
                    break;
                }
            }

            if ($isMultipleRecords) {
                $removeUnmappedBool = is_bool($removeUnmapped) ? $removeUnmapped : false;
                $result = [];
                foreach ($data as $record) {
                    if (is_array($record)) {
                        $result[] = $this->mapRecord($record, $fieldMapping, $removeUnmappedBool);
                    }
                }
                return $result;
            }
        }

        // Handle single record
        if (is_array($data)) {
            $removeUnmappedBool = is_bool($removeUnmapped) ? $removeUnmapped : false;
            return $this->mapRecord($data, $fieldMapping, $removeUnmappedBool);
        }

        return $data;
    }

    /**
     * @param array<mixed> $record
     * @param array<string, mixed> $fieldMapping
     * @return array<mixed>
     */
    private function mapRecord(array $record, array $fieldMapping, bool $removeUnmapped): array
    {
        $mappedRecord = [];

        // Apply field mappings
        foreach ($fieldMapping as $sourceField => $targetField) {
            if (is_string($targetField)) {
                // Simple field renaming: 'old_name' => 'new_name'
                if (isset($record[$sourceField])) {
                    $mappedRecord[$targetField] = $record[$sourceField];
                }
            } elseif (is_array($targetField)) {
                // Complex mapping with transformation
                $target = $targetField['target'] ?? $sourceField;
                $transform = $targetField['transform'] ?? null;
                $defaultValue = $targetField['default'] ?? null;

                $value = $record[$sourceField] ?? $defaultValue;

                // Apply transformation if defined
                if ($transform !== null && $value !== null) {
                    $value = match ($transform) {
                        'upper' => is_string($value) ? strtoupper($value) : $value,
                        'lower' => is_string($value) ? strtolower($value) : $value,
                        'trim' => is_string($value) ? trim($value) : $value,
                        'int' => is_scalar($value) ? (int) $value : $value,
                        'float' => is_scalar($value) ? (float) $value : $value,
                        'string' => is_scalar($value) ? (string) $value : $value,
                        'bool' => (bool) $value,
                        'date' => $this->parseDate($value),
                        'json_decode' => is_string($value) ? json_decode($value, true) : $value,
                        'json_encode' => json_encode($value),
                        default => is_callable($transform) ? $transform($value) : $value
                    };
                }

                /** @var int|string $targetKey */
                $targetKey = $target;
                $mappedRecord[$targetKey] = $value;
            }
        }

        // Keep unmapped fields if requested
        if (! $removeUnmapped) {
            foreach ($record as $key => $value) {
                if (! array_key_exists($key, $fieldMapping) && ! isset($mappedRecord[$key])) {
                    $mappedRecord[$key] = $value;
                }
            }
        }

        return $mappedRecord;
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        if (is_string($value) || is_int($value)) {
            try {
                if (is_int($value)) {
                    return new \DateTimeImmutable('@' . $value);
                }
                return new \DateTimeImmutable($value);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }
}
