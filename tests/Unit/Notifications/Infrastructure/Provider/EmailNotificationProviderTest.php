<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Notifications\Infrastructure\Provider;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Notifications\Domain\Model\NotificationMessage;
use BoutDeCode\ETLCoreBundle\Notifications\Infrastructure\Provider\EmailNotificationProvider;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class EmailNotificationProviderTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getCodeShouldReturnEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $provider = new EmailNotificationProvider($mailer, 'from@example.com', ['to@example.com']);

        $this->assertSame('email', $provider->getCode());
    }

    #[Test]
    public function notifyShouldDoNothingWhenNoRecipientConfigured(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('send');

        $provider = new EmailNotificationProvider($mailer, 'from@example.com', []);

        $provider->notify($this->createMessage(PipelineHistoryStatusEnum::COMPLETED));
    }

    #[Test]
    public function notifyShouldSendEmailWithSuccessSubject(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (RawMessage $email): bool {
                $this->assertInstanceOf(Email::class, $email);
                $this->assertSame('[ETL] Workflow "daily-import" succeeded', $email->getSubject());
                $this->assertSame('from@example.com', $email->getFrom()[0]->getAddress());
                $this->assertSame('to@example.com', $email->getTo()[0]->getAddress());

                return true;
            }));

        $provider = new EmailNotificationProvider($mailer, 'from@example.com', ['to@example.com']);

        $provider->notify($this->createMessage(PipelineHistoryStatusEnum::COMPLETED));
    }

    #[Test]
    public function notifyShouldSendEmailWithFailureSubjectAndErrors(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (RawMessage $email): bool {
                $this->assertInstanceOf(Email::class, $email);
                $this->assertSame('[ETL] Workflow "daily-import" failed', $email->getSubject());
                $this->assertStringContainsString('step_one: boom', (string) $email->getTextBody());

                return true;
            }));

        $provider = new EmailNotificationProvider($mailer, 'from@example.com', ['to@example.com']);

        $provider->notify($this->createMessage(PipelineHistoryStatusEnum::FAILED, [
            'step_one' => 'boom',
        ]));
    }

    /**
     * @param array<string, string> $errors
     */
    private function createMessage(PipelineHistoryStatusEnum $status, array $errors = []): NotificationMessage
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getName')->willReturn('daily-import');

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getWorkflow')->willReturn($workflow);

        return new NotificationMessage($workflow, $pipeline, $status, $errors);
    }
}
