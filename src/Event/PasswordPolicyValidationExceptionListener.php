<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Event;

use Choks\PasswordPolicy\Exception\PolicyCheckException;
use Choks\PasswordPolicy\Violation\Violation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class PasswordPolicyValidationExceptionListener
{
    private bool $throwJsonBadRequest;

    public function __construct(bool $throwJsonBadRequest)
    {
        $this->throwJsonBadRequest = $throwJsonBadRequest;
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof PolicyCheckException || $this->throwJsonBadRequest !== true) {
            return;
        }

        $request     = $event->getRequest();
        $contentType = $request->headers->get('content-type');
        $format      = $request->attributes->get('_route_params')['_format'] ?? null;

        if ($format !== 'json' && \strtolower((string)$contentType) !== 'application/json') {
            return;
        }

        // sends the modified response object to the event
        $event->setResponse($this->getJsonResponse($exception));
    }

    private function getJsonResponse(PolicyCheckException $throwable): JsonResponse
    {
        $violations = [];

        /** @var Violation $violation */
        foreach ($throwable->getViolations() as $violation) {
            $violations[] = $violation->getMessage();
        }

        return new JsonResponse(
            [
                'error' => [
                    'message'    => $throwable->getMessage(),
                    'violations' => $violations,
                ],
            ],
            Response::HTTP_BAD_REQUEST
        );
    }
}