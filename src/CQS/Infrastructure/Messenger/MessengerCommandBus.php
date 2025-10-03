<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\CQS\Infrastructure\Messenger;

use BoutDeCode\ETLCoreBundle\CQS\Application\Exception\CommandHandlerException;
use BoutDeCode\ETLCoreBundle\CQS\Application\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\Command;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerCommandBus implements CommandBus
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $commandBus,
        private readonly Logger $logger
    ) {
        $this->messageBus = $commandBus;
    }

    /**
     * @throws \Throwable
     */
    public function dispatch(Command $command): mixed
    {
        $commandName = get_class($command);
        $this->logger->start($commandName, $command);

        try {
            $response = $this->handle($command);

            $this->logger->success($commandName, $response, true);

            return $response;
        } catch (HandlerFailedException $exception) {
            $handlerException = new CommandHandlerException('Error during process', $exception);
            $firstException = $handlerException->getFirst();

            $this->logger->error($commandName, $handlerException->getMessage());

            throw $firstException ?? $handlerException;
        }
    }
}
