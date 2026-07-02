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
                $body = (string) $email->getHtmlBody();

                $this->assertInstanceOf(Email::class, $email);
                $this->assertSame('[ETL] Workflow "daily-import" failed', $email->getSubject());
                $this->assertStringContainsString('<li>step_one: boom</li>', $body);

                return true;
            }));

        $provider = new EmailNotificationProvider($mailer, 'from@example.com', ['to@example.com']);

        $provider->notify($this->createMessage(PipelineHistoryStatusEnum::FAILED, [
            'step_one' => 'boom',
        ]));
    }

    #[Test]
    public function notifyShouldIncludePipelineInfoInTheEmailBody(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (RawMessage $email): bool {
                $body = (string) $email->getHtmlBody();

                $this->assertStringContainsString('ID: pipeline-42', $body);
                $this->assertStringContainsString('Name: nightly-run', $body);
                $this->assertStringContainsString('Scheduled at: 2026-07-02T02:00:00+00:00', $body);
                $this->assertStringContainsString('Started at: 2026-07-02T03:00:00+00:00', $body);
                $this->assertStringContainsString('Finished at: 2026-07-02T03:00:05+00:00', $body);
                $this->assertStringContainsString('Duration: 5000 ms', $body);

                return true;
            }));

        $provider = new EmailNotificationProvider($mailer, 'from@example.com', ['to@example.com']);

        $provider->notify($this->createMessage(PipelineHistoryStatusEnum::COMPLETED));
    }

    #[Test]
    public function notifyShouldIncludeThePipelineResultInAPreTag(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (RawMessage $email): bool {
                $body = (string) $email->getHtmlBody();

                $this->assertMatchesRegularExpression('#<pre>.*&quot;records&quot;: 42.*</pre>#s', $body);

                return true;
            }));

        $provider = new EmailNotificationProvider($mailer, 'from@example.com', ['to@example.com']);

        $provider->notify($this->createMessage(PipelineHistoryStatusEnum::COMPLETED, [], [
            'records' => 42,
        ]));
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function notifyShouldRenderNaInThePreTagWhenNoResult(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (RawMessage $email): bool {
                $this->assertStringContainsString('<pre>n/a</pre>', (string) $email->getHtmlBody());

                return true;
            }));

        $provider = new EmailNotificationProvider($mailer, 'from@example.com', ['to@example.com']);

        $provider->notify($this->createMessage(PipelineHistoryStatusEnum::COMPLETED));
    }

    /**
     * @param array<string, string> $errors
     */
    private function createMessage(
        PipelineHistoryStatusEnum $status,
        array $errors = [],
        mixed $result = null,
    ): NotificationMessage {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getName')->willReturn('daily-import');

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getWorkflow')->willReturn($workflow);
        $pipeline->method('getId')->willReturn('pipeline-42');
        $pipeline->method('getName')->willReturn('nightly-run');
        $pipeline->method('getScheduledAt')->willReturn(new \DateTimeImmutable('2026-07-02T02:00:00+00:00'));
        $pipeline->method('getStartedAt')->willReturn(new \DateTimeImmutable('2026-07-02T03:00:00+00:00'));
        $pipeline->method('getFinishedAt')->willReturn(new \DateTimeImmutable('2026-07-02T03:00:05+00:00'));

        return new NotificationMessage($workflow, $pipeline, $status, $errors, $result);
    }
}
