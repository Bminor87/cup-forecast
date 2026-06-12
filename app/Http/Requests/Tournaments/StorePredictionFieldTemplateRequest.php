<?php

namespace App\Http\Requests\Tournaments;

use App\Domain\Tournaments\PredictionFieldTemplateCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePredictionFieldTemplateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $templateKeys = collect(app(PredictionFieldTemplateCatalog::class)->templates())
            ->pluck('key')
            ->all();

        return [
            'template_key' => ['required', 'string', Rule::in($templateKeys)],
        ];
    }
}
