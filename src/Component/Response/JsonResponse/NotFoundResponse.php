<?php

namespace App\Component\Response\JsonResponse;

class NotFoundResponse extends FailureResponse
{
    public const STATUS_CODE = 404; // Not Found

    public function __construct(
        ?string $message =              'common.messages.not_found',
        ?array  $messageParameters =    [],
        ?array  $data =                 [],
        ?array  $warnings =             [],
        array   $headers =              []
    )
    {
        parent::__construct(
            $message,
            $messageParameters,
            [],
            $data,
            $warnings,
            self::STATUS_CODE,
            $headers
        );
    }
}
