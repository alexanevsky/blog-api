<?php

namespace App\Component\Response\JsonResponse;

class DeletedResponse extends FailureResponse
{
    public const STATUS_CODE = 410; // Gone

    public function __construct(
        ?string $message =              'common.messages.deleted',
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
