<?php

namespace App\Component\Response\JsonResponse;

class SuccessResponse extends JsonResponse
{
    public const STATUS_CODE = 200; // OK

    public function __construct(
        ?string $message =              '',
        ?array  $messageParameters =    [],
        ?array  $data =                 [],
        ?array  $warnings =             [],
        int     $status =               self::STATUS_CODE,
        array   $headers =              []
    )
    {
        parent::__construct(
            true,
            $message,
            $messageParameters,
            $data,
            [],
            $warnings,
            $status,
            $headers
        );
    }
}
