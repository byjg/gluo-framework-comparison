<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * An API only: an HTTP error (404, 403, 422 from #[MapRequestPayload]) answers {message}
 * as JSON, also for a request without "Accept: application/json"
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class JsonExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse(
                ['message' => $exception->getMessage()],
                $exception->getStatusCode(),
                $exception->getHeaders(),
            ));
        }
    }
}
