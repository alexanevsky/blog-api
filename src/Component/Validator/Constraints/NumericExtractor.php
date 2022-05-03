<?php

namespace App\Component\Validator\Constraints;

use Alexanevsky\DataResolver\RequiementExtractor\Extractor\AbstractExtractor;

class NumericExtractor extends AbstractExtractor
{
    public const CONSTRAINT_CLASS = Numeric::class;

    public function extract(): array
    {
        return [
            'numeric' => $this->constraint->isNumeric
        ];
    }
}
