<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Transformer;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute\AsExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractTransformerStep;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

#[AsExecutableStep(
    code: 'etl.transformer.filter',
    configurationDescription: [
        'filterExpression' => 'A Symfony Expression Language expression used to filter items. The expression can reference the current item using the variable item.',
    ],
)]
final class FilterTransformStep extends AbstractTransformerStep
{
    public function __construct(
        private readonly string $filterExpression = 'item'
    ) {
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<mixed>
     */
    public function transform(mixed $data, Context $context, array $configuration = []): array
    {
        if (! is_array($data)) {
            throw new \InvalidArgumentException('Data must be an array');
        }

        $filterExpression = $configuration['filterExpression'] ?? $this->configuration['filterExpression'] ?? $this->filterExpression;
        $filterExpressionStr = is_string($filterExpression) ? $filterExpression : $this->filterExpression;
        $expressionLanguage = new ExpressionLanguage();

        return array_values(array_filter($data, fn (mixed $item): bool => (bool) $expressionLanguage->evaluate($filterExpressionStr, [
            'item' => (object) $item,
        ])));
    }
}
