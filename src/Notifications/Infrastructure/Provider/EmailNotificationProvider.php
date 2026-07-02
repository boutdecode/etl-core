<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Notifications\Infrastructure\Provider;

use BoutDeCode\ETLCoreBundle\Notifications\Domain\Attribute\AsNotificationProvider;
use BoutDeCode\ETLCoreBundle\Notifications\Domain\Model\NotificationMessage;
use BoutDeCode\ETLCoreBundle\Notifications\Domain\Model\NotificationProvider;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsNotificationProvider(code: 'email')]
final readonly class EmailNotificationProvider implements NotificationProvider
{
    /**
     * @param string[] $to
     */
    public function __construct(
        private MailerInterface $mailer,
        private string $from,
        private array $to = [],
        private string $subjectPrefix = '[ETL]',
    ) {
    }

    public function getCode(): string
    {
        return 'email';
    }

    public function notify(NotificationMessage $message): void
    {
        if ($this->to === []) {
            return;
        }

        $outcome = $message->status === PipelineHistoryStatusEnum::COMPLETED ? 'succeeded' : 'failed';

        $email = (new Email())
            ->from($this->from)
            ->to(...$this->to)
            ->subject(sprintf('%s Workflow "%s" %s', $this->subjectPrefix, $message->workflow->getName(), $outcome))
            ->html($this->buildBody($message));

        $this->mailer->send($email);
    }

    private function buildBody(NotificationMessage $message): string
    {
        $pipeline = $message->pipeline;

        $html = sprintf('<p>Workflow: <strong>%s</strong><br>', htmlspecialchars($message->workflow->getName()));
        $html .= sprintf('Status: %s</p>', htmlspecialchars($message->status->value));

        $html .= '<p>Pipeline:<br>';
        $html .= sprintf('ID: %s<br>', htmlspecialchars($pipeline->getId()));

        if ($pipeline->getName() !== null) {
            $html .= sprintf('Name: %s<br>', htmlspecialchars($pipeline->getName()));
        }

        $html .= sprintf('Scheduled at: %s<br>', htmlspecialchars($this->formatDate($pipeline->getScheduledAt())));
        $html .= sprintf('Started at: %s<br>', htmlspecialchars($this->formatDate($pipeline->getStartedAt())));
        $html .= sprintf('Finished at: %s', htmlspecialchars($this->formatDate($pipeline->getFinishedAt())));

        $durationMs = $this->computeDurationMs($pipeline->getStartedAt(), $pipeline->getFinishedAt());
        if ($durationMs !== null) {
            $html .= sprintf('<br>Duration: %d ms', $durationMs);
        }

        $html .= '</p>';

        $html .= sprintf('<p>Result:</p><pre>%s</pre>', htmlspecialchars($this->formatResult($message->result)));

        if ($message->errors !== []) {
            $html .= '<p>Errors:</p><ul>';
            foreach ($message->errors as $step => $error) {
                $html .= sprintf('<li>%s: %s</li>', htmlspecialchars($step), htmlspecialchars($error));
            }
            $html .= '</ul>';
        }

        return $html;
    }

    private function formatResult(mixed $result): string
    {
        if ($result === null) {
            return 'n/a';
        }

        if (is_string($result)) {
            return $result;
        }

        if (is_scalar($result)) {
            return (string) $result;
        }

        return (string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function formatDate(?\DateTimeImmutable $date): string
    {
        return $date?->format('c') ?? 'n/a';
    }

    private function computeDurationMs(?\DateTimeImmutable $startedAt, ?\DateTimeImmutable $finishedAt): ?int
    {
        if ($startedAt === null || $finishedAt === null) {
            return null;
        }

        return (int) round(((float) $finishedAt->format('U.u') - (float) $startedAt->format('U.u')) * 1000);
    }
}
