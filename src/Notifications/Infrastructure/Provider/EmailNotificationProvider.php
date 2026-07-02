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
            ->text($this->buildBody($message));

        $this->mailer->send($email);
    }

    private function buildBody(NotificationMessage $message): string
    {
        $lines = [
            sprintf('Workflow: %s', $message->workflow->getName()),
            sprintf('Status: %s', $message->status->value),
        ];

        if ($message->errors !== []) {
            $lines[] = '';
            $lines[] = 'Errors:';
            foreach ($message->errors as $step => $error) {
                $lines[] = sprintf('- %s: %s', $step, $error);
            }
        }

        return implode("\n", $lines);
    }
}
