<?php

namespace App\Component\Response\JsonResponse;

class AccessDeniedResponse extends FailureResponse
{
    public const STATUS_CODE = 403; // Forbidden

    public function __construct(
        ?string $message =              'common.messages.access_denied',
        ?array  $messageParameters =    [],
        ?array  $data =                 [],
        ?array  $warnings =             [],
        array   $headers =              [],
        bool    $needAuth =             false
    )
    {
        parent::__construct(
            $message,
            $messageParameters,
            [],
            $data,
            $warnings,
            $needAuth ? NeedAuthResponse::STATUS_CODE : self::STATUS_CODE,
            $headers
        );
    }
}
