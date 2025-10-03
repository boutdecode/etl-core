<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\CQS\Infrastructure\Messenger;

use BoutDeCode\ETLCoreBundle\CQS\Application\Exception\QueryHandlerException;
use BoutDeCode\ETLCoreBundle\CQS\Application\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Query\Query;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Query\QueryBus;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerQueryBus implements QueryBus
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $queryBus,
        private readonly ?Logger $logger = null
    ) {
        $this->messageBus = $queryBus;
    }

    /**
     * @throws \Throwable
     */
    public function ask(Query $query): mixed
    {
        $queryName = get_class($query);
        $this->logger?->start($queryName, $query);

        try {
            $response = $this->handle($query);

            $this->logger?->success($queryName, $response, true);

            return $response;
        } catch (HandlerFailedException $exception) {
            $handlerException = new QueryHandlerException('Error during process', $exception);
            $firstException = $handlerException->getFirst();

            $this->logger?->error($queryName, $handlerException->getMessage());

            throw $firstException ?? $handlerException;
        }
    }
}
