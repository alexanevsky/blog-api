<?php

namespace App\EventListener;

use App\Component\Converter\ArrayConverter;
use App\Component\Exception\LoggedException;
use App\Component\Exception\TranslatableException;
use App\Component\Response\JsonResponse\FailureResponse;
use App\Component\Response\JsonResponse\JsonResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class KernelEventListener
{
    /**
     * Options used while encoding data to JSON.
     */
    private const JSON_ENCODING_OPTIONS = JSON_UNESCAPED_UNICODE;

    private const HEADER_NAME_KEYS_CASE = 'X-Keys-Case';

    private const SKIP_LOGGING_ON_URI_EXTENSION = ['jpg', 'png', 'svg', 'ico', 'css', 'js'];

    public function __construct(
        private ArrayConverter          $arrayConverter,
        private ContainerBagInterface   $parameters,
        private LoggerInterface         $exceptionLogger,
        private LoggerInterface         $failedResponseLogger,
        private TranslatorInterface     $translator
    )
    {}

    public function catchResponse(ResponseEvent $event): void
    {
        $this->convertResponseData($event);
        $this->convertResponseKeys($event);
        $this->setResponseEncodingOptions($event);
        $this->logResponse($event);
    }

    public function catchException(ExceptionEvent $event): void
    {
        $this->logException($event);
        $this->exceptionToResponse($event);
    }

    public function convertResponseData(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$response instanceof JsonResponse) {
            return;
        }

        $data = json_decode($response->getContent(), true);

        // Translate given message
        if (!empty($data['message']) && 1 === preg_match('/^([\a-z\d\.]+)$/u', $data['message'])) {
            $data['message'] = $this->translator->trans($data['message'], $data['message_parameters'] ?? []);
        }

        unset($data['message_parameters']);

        // Translate given errors
        if (!empty($data['errors'])) {
            foreach ($data['errors'] as $key => $error) {
                if (is_array($error) && isset($error['message'])) {
                    $data['errors'][$key] = $this->translator->trans($error['message'], $error['message_parameters'] ?? []);
                } elseif (is_string($error) && 1 === preg_match('/^([\a-z\d\.]+)$/u', $error)) {
                    $data['errors'][$key] = $this->translator->trans($error);
                }
            }
        }

        // Translate given warnings
        if (!empty($data['warnings'])) {
            foreach ($data['warnings'] as $key => $warning) {
                if (is_array($warning) && isset($warning['message'])) {
                    $data['warnings'][$key] = $this->translator->trans($warning['message'], $warning['message_parameters'] ?? []);
                } elseif (is_string($warning) && 1 === preg_match('/^([\a-z\d\.]+)$/u', $warning)) {
                    $data['warnings'][$key] = $this->translator->trans($warning);
                }
            }

            $data['warnings'] = array_values($data['warnings']);
        }

        $response->setData($data);
    }

    public function convertResponseKeys(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$response instanceof JsonResponse) {
            return;
        }

        $request = $event->getRequest();
        $case = strtolower($request->headers->get(self::HEADER_NAME_KEYS_CASE));

        if ('snake' === $case) {
            return;
        }

        $data = json_decode($response->getContent(), true);
        $data = $this->arrayConverter->keysToCamel($data, false);

        $response->setData($data);
    }

    public function setResponseEncodingOptions(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if ($response instanceof JsonResponse) {
            $response->setEncodingOptions(self::JSON_ENCODING_OPTIONS);
        }
    }

    public function logResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$response instanceof JsonResponse) {
            return;
        } elseif (!$this->isLoggingRequired($event)) {
            return;
        }


        $statusCode = $response->getStatusCode();
        $uri = $event->getRequest()->getRequestUri();
        $data = json_decode($response->getContent(), true);

        $message = !empty($data['message'])
            ? sprintf('%s %s (%s)', $statusCode, $uri, $data['message'])
            : sprintf('%s %s', $statusCode, $uri);

        $requestContext = $this->extractRequestAsArray($event);
        $context = !empty($data['errors'])
            ? ['errors' => $data['errors'], 'request' => $requestContext]
            : ['request' => $requestContext];

        $level = (500 <= $response->getStatusCode()) ? 'critical' : 'error';

        $this->failedResponseLogger->$level($message, $context);
    }

    public function logException(ExceptionEvent $event): void
    {
        if (!$this->isLoggingRequired($event)) {
            return;
        }

        /** @var LoggedException|\Throwable */
        $exception = $event->getThrowable();
        $requestContext = $this->extractRequestAsArray($event);

        if ($exception instanceof LoggedException) {
            $logger = $exception->getLogger() ?? $this->exceptionLogger;
            $message = $exception->getExceptionedClass()
                ? sprintf('%s throws %s: %s', $exception->getExceptionedClass(), $exception::class, $exception->getMessage())
                : sprintf('%s: %s', $exception::class, $exception->getMessage());
            $context = array_filter([
                'data' => $exception->getLoggedContext(),
                'request' => $requestContext
            ]);

            $logger->critical($message, $context);
        } else {
            $message = sprintf('%s: %s', $exception::class, $exception->getMessage());
            $context = ['request' => $requestContext];

            $this->exceptionLogger->critical($message, $context);
        }
    }

    public function exceptionToResponse(ExceptionEvent $event): void
    {
        /** @var HttpExceptionInterface|TranslatableException|\Exception */
        $exception = $event->getThrowable();
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : null;

        [$message, $messageParameters] = $exception instanceof TranslatableException
            ? [$exception->getMessageKey(), $exception->getMessageParameters()]
            : [null, []];

        if (!$message) {
            if ('dev' === $this->parameters->get('kernel.environment')) {
                $message = $exception->getMessage();
            } elseif (401 === $statusCode) {
                $message = 'common.messages.access_denied';
            } elseif (403 === $statusCode) {
                $message = 'common.messages.access_denied';
            } elseif (404 === $statusCode) {
                $message = 'common.messages.not_found';
            } elseif (410 === $statusCode) {
                $message = 'common.messages.deleted';
            } else {
                $message = 'common.messages.failed';
            }
        }

        $response = new FailureResponse($message, $messageParameters, status: $statusCode ?: FailureResponse::STATUS_CODE);
        $event->setResponse($response);
    }

    private function isLoggingRequired(ResponseEvent|ExceptionEvent $event): bool
    {
        $uri = $event->getRequest()->getRequestUri();

        if ($event instanceof ExceptionEvent) {
            /** @var HttpExceptionInterface|\Throwable */
            $exception = $event->getThrowable();
            $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : null;
        } else {
            $statusCode = $event->getResponse()->getStatusCode();
        }

        if (!$statusCode) {
            return true;
        }

        if (400 > $statusCode) {
            return false;
        } elseif (404 === $statusCode && false !== strrpos($uri, '.') && in_array(substr($uri, strrpos($uri, '.') + 1), self::SKIP_LOGGING_ON_URI_EXTENSION)) {
            return false;
        }

        return true;
    }

    private function extractRequestAsArray(KernelEvent $event): array
    {
        $request = $event->getRequest();

        return array_filter([
            'request_method' => $request->getMethod(),
            'content_type' =>   $request->getContentType(),
            'content' =>        $request->getContent(),
            'request' =>        'application/json' !== $request->getContentType() ? $request->request->all() : null,
            'query' =>          $request->query->all(),
            'server' =>         array_filter($request->server->all(), function($key) {return in_array($key, [
                'HTTP_HOST',
                'HTTP_USER_AGENT',
                'REMOTE_ADDR',
                'REQUEST_URI'
            ]);}, ARRAY_FILTER_USE_KEY),
            'files' =>          $request->files->all(),
            'cookies' =>        $request->cookies->all()
        ]);
    }
}
