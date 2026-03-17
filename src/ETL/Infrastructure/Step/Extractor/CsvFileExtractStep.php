<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Extractor;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute\AsExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractExtractorStep;
use function Flow\ETL\Adapter\CSV\from_csv;
use function Flow\ETL\DSL\data_frame;
use function Flow\ETL\DSL\to_array;

#[AsExecutableStep(
    code: 'etl.extractor.csv_file',
    configurationDescription: [
        'source' => 'Path to the CSV file to extract data from',
        'delimiter' => 'Field delimiter (default: ",")',
        'hasHeader' => 'Whether the first row contains column names (default: true)',
        'enclosure' => 'Field enclosure character (default: \'"\')',
        'escape' => 'Escape character (default: "\\\\")',
    ],
)]
class CsvFileExtractStep extends AbstractExtractorStep
{
    public function __construct(
        private readonly string $delimiter = ',',
        private readonly bool $hasHeader = true,
    ) {
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<mixed>
     */
    public function extract(mixed $source, Context $context, array $configuration = []): array
    {
        if (is_array($source)) {
            $source = $source['source'] ?? $configuration['source'] ?? $this->configuration['source'] ?? null;
        }

        if (! is_string($source)) {
            throw new \InvalidArgumentException('Source must be a string representing the file path.');
        }

        if (! file_exists($source)) {
            throw new \InvalidArgumentException("File not found: {$source}");
        }

        $delimiter = $configuration['delimiter'] ?? $this->configuration['delimiter'] ?? $this->delimiter;
        $hasHeader = $configuration['hasHeader'] ?? $this->configuration['hasHeader'] ?? $this->hasHeader;
        $enclosure = $configuration['enclosure'] ?? '"';
        $escape = $configuration['escape'] ?? '\\';

        $delimiterStr = is_string($delimiter) ? $delimiter : $this->delimiter;
        $hasHeaderBool = is_bool($hasHeader) ? $hasHeader : $this->hasHeader;
        $enclosureStr = is_string($enclosure) ? $enclosure : '"';
        $escapeStr = is_string($escape) ? $escape : '\\';

        $result = [];

        data_frame()
            ->read(from_csv(
                $source,
                with_header: $hasHeaderBool,
                separator: $delimiterStr,
                enclosure: $enclosureStr,
                escape: $escapeStr,
            ))
            ->collect()
            ->write(to_array($result))
            ->run();

        return $result;
    }
}
