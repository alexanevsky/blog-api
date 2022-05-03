<?php

namespace App\Component\Response\JsonResponse;

class FailureResponse extends JsonResponse
{
    public const STATUS_CODE = 400; // Bad Request

    public function __construct(
        ?string $message =              '',
        ?array  $messageParameters =    [],
        ?array  $errors =               [],
        ?array  $data =                 [],
        ?array  $warnings =             [],
        int     $status =               self::STATUS_CODE,
        array   $headers =              []
    )
    {
        parent::__construct(
            false,
            $message,
            $messageParameters,
            $data,
            $errors,
            $warnings,
            $status,
            $headers
        );
    }
}
