<?php

namespace App\Component\Response\JsonResponse;

class NeedAuthResponse extends FailureResponse
{
    public const STATUS_CODE = 401; // Unauthorized

    public function __construct(
        ?string $message =              'common.messages.need_auth',
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
