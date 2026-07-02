<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Notifications\Infrastructure\Middleware;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Notifications\Domain\Model\NotificationMessage;
use BoutDeCode\ETLCoreBundle\Notifications\Domain\Model\NotificationProvider;
use BoutDeCode\ETLCoreBundle\Notifications\Domain\Resolver\NotificationProviderResolver;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;

final readonly class NotificationMiddleware implements Middleware
{
    public function __construct(
        private NotificationProviderResolver $notificationProviderResolver,
        private ?Logger $logger = null,
    ) {
    }

    public function process(Context $context, callable $next): Context
    {
        $pipeline = $context->getPipeline();

        if ($pipeline === null) {
            /** @var Context $result */
            $result = $next($context);

            return $result;
        }

        $workflow = $pipeline->getWorkflow();
        $errors = $context->getErrors();
        $status = $errors !== [] ? PipelineHistoryStatusEnum::FAILED : PipelineHistoryStatusEnum::COMPLETED;

        $notificationConfiguration = $this->extractNotificationConfiguration($workflow->getConfiguration());

        $shouldNotify = $status === PipelineHistoryStatusEnum::FAILED
            ? $notificationConfiguration['on_failure']
            : $notificationConfiguration['on_success'];

        if ($shouldNotify) {
            $this->notify(
                $notificationConfiguration['providers'],
                new NotificationMessage($workflow, $pipeline, $status, $errors),
                $context,
            );
        }

        /** @var Context $result */
        $result = $next($context);

        return $result;
    }

    /**
     * @param string[]|null $providerCodes
     */
    private function notify(?array $providerCodes, NotificationMessage $message, Context $context): void
    {
        $providers = $this->resolveProviders($providerCodes);

        foreach ($providers as $provider) {
            try {
                $provider->notify($message);
            } catch (\Throwable $exception) {
                $this->logger?->error('Notification provider failed to send notification', $context, $exception, [
                    'provider' => $provider->getCode(),
                ]);
            }
        }
    }

    /**
     * @param string[]|null $providerCodes
     *
     * @return NotificationProvider[]
     */
    private function resolveProviders(?array $providerCodes): array
    {
        if ($providerCodes === null) {
            return $this->notificationProviderResolver->list();
        }

        $providers = [];
        foreach ($providerCodes as $code) {
            $provider = $this->notificationProviderResolver->resolve($code);
            if ($provider !== null) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    /**
     * @param array<string, mixed> $configuration
     *
     * @return array{on_success: bool, on_failure: bool, providers: string[]|null}
     */
    private function extractNotificationConfiguration(array $configuration): array
    {
        $notifications = $configuration['notifications'] ?? [];
        $notifications = is_array($notifications) ? $notifications : [];

        $providers = $notifications['providers'] ?? null;

        return [
            'on_success' => (bool) ($notifications['on_success'] ?? false),
            'on_failure' => (bool) ($notifications['on_failure'] ?? false),
            'providers' => is_array($providers) ? array_values(array_filter($providers, 'is_string')) : null,
        ];
    }
}
