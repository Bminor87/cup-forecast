<?php

namespace App\Domain\Tournaments\Validation;

use App\Domain\Tournaments\Models\PredictionField;

class PredictionResultValidationService
{
    public function __construct(
        protected PredictionSubmissionValidationService $submissionValidation,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function validate(PredictionField $field, array $payload): array
    {
        return $this->submissionValidation->validate($field, $payload);
    }
}
