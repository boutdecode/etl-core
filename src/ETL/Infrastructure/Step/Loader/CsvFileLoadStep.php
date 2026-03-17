<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Loader;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute\AsExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractLoaderStep;
use function Flow\ETL\Adapter\CSV\to_csv;
use function Flow\ETL\DSL\data_frame;
use function Flow\ETL\DSL\from_array;
use function Flow\ETL\DSL\overwrite;

#[AsExecutableStep(
    code: 'etl.loader.csv_file',
    configurationDescription: [
        'destination' => 'Path to the CSV file to write data into',
        'delimiter' => 'Field delimiter (default: ",")',
        'withHeader' => 'Whether to write column names as first row (default: true)',
        'enclosure' => 'Field enclosure character (default: \'"\')',
        'escape' => 'Escape character (default: "\\\\")',
    ],
)]
final class CsvFileLoadStep extends AbstractLoaderStep
{
    public function __construct(
        private readonly string $delimiter = ',',
        private readonly bool $withHeader = true,
        private readonly string $enclosure = '"',
        private readonly string $escape = '\\',
    ) {
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public function load(mixed $data, mixed $destination, Context $context, array $configuration = []): bool
    {
        if (! is_string($destination)) {
            throw new \InvalidArgumentException('File path must be a string');
        }

        $delimiter = $configuration['delimiter'] ?? $this->configuration['delimiter'] ?? $this->delimiter;
        $withHeader = $configuration['withHeader'] ?? $this->configuration['withHeader'] ?? $this->withHeader;
        $enclosure = $configuration['enclosure'] ?? $this->configuration['enclosure'] ?? $this->enclosure;
        $escape = $configuration['escape'] ?? $this->configuration['escape'] ?? $this->escape;

        $delimiterStr = is_string($delimiter) ? $delimiter : $this->delimiter;
        $withHeaderBool = is_bool($withHeader) ? $withHeader : $this->withHeader;
        $enclosureStr = is_string($enclosure) ? $enclosure : $this->enclosure;
        $escapeStr = is_string($escape) ? $escape : $this->escape;

        /** @var iterable<array<mixed>> $iterableData */
        $iterableData = is_iterable($data) ? $data : [$data];

        data_frame()
            ->read(from_array($iterableData))
            ->collect()
            ->mode(overwrite())
            ->write(to_csv(
                $destination,
                with_header: $withHeaderBool,
                separator: $delimiterStr,
                enclosure: $enclosureStr,
                escape: $escapeStr,
            ))
            ->run();

        return true;
    }
}
