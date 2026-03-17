<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute;

/**
 * Marks a class as an executable ETL step and declares its metadata.
 *
 * - `code`  : the unique machine identifier used to resolve the step (e.g. "etl.extractor.csv_file").
 * - `configurationDescription` : a map of configuration keys → human-readable descriptions,
 *   returned verbatim by {@see \BoutDeCode\ETLCoreBundle\ETL\Domain\Model\ExecutableStep::getConfigurationDescription()}.
 *
 * Usage:
 * <code>
 * #[AsExecutableStep(
 *     code: 'etl.extractor.csv_file',
 *     configurationDescription: [
 *         'source'    => 'Path to the CSV file',
 *         'delimiter' => 'Field delimiter (default: ",")',
 *     ],
 * )]
 * class CsvFileExtractStep extends AbstractExtractorStep { … }
 * </code>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsExecutableStep
{
    /**
     * @param string               $code                     Unique step identifier
     * @param array<string, mixed> $configurationDescription Map of config key → description
     */
    public function __construct(
        public string $code,
        public array $configurationDescription = [],
    ) {
    }
}
