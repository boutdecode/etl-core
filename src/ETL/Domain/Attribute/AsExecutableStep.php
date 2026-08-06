<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute;

/**
 * Marks a class as an executable ETL step and declares its metadata.
 *
 * - `code`  : the unique machine identifier used to resolve the step (e.g. "etl.extractor.csv_file").
 * - `configurationDescription` : a map of configuration keys → human-readable descriptions,
 *   returned verbatim by {@see \BoutDeCode\ETLCoreBundle\ETL\Domain\Model\ExecutableStep::getConfigurationDescription()}.
 * - `configurationSchema` : a map of configuration keys → expected shape, returned verbatim by
 *   {@see \BoutDeCode\ETLCoreBundle\ETL\Domain\Model\ExecutableStep::getConfigurationSchema()} and enforced by
 *   {@see \BoutDeCode\ETLCoreBundle\ETL\Domain\Validator\ConfigurationSchemaValidator} whenever the step's
 *   configuration is set.
 *
 * Usage:
 * <code>
 * #[AsExecutableStep(
 *     code: 'etl.extractor.csv_file',
 *     configurationDescription: [
 *         'source'    => 'Path to the CSV file',
 *         'delimiter' => 'Field delimiter (default: ",")',
 *     ],
 *     configurationSchema: [
 *         'source'    => ['type' => 'string'],
 *         'delimiter' => ['type' => 'string'],
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
     * @param array<string, mixed> $configurationSchema      Map of config key → expected shape (see
     *                                                        {@see \BoutDeCode\ETLCoreBundle\ETL\Domain\Validator\ConfigurationSchemaValidator})
     */
    public function __construct(
        public string $code,
        public array $configurationDescription = [],
        public array $configurationSchema = [],
    ) {
    }
}
