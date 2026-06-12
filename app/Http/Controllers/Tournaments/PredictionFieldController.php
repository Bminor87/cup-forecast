<?php

namespace App\Http\Controllers\Tournaments;

use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\PredictionFieldTemplateCatalog;
use App\Domain\Tournaments\Validation\PredictionFieldValidationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StorePredictionFieldRequest;
use App\Http\Requests\Tournaments\StorePredictionFieldTemplateRequest;
use App\Http\Requests\Tournaments\UpdatePredictionFieldRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PredictionFieldController extends Controller
{
    public function store(StorePredictionFieldRequest $request, Team $team, PredictionFieldValidationService $fieldValidator): RedirectResponse
    {
        $tournament = Tournament::query()->findOrFail($team->id);

        Gate::authorize('create', [PredictionField::class, $tournament]);

        $payload = $request->validated();
        $fieldValidator->validateDefinition($payload);

        $tournament->predictionFields()->create($payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prediction field created.')]);

        return back();
    }

    public function storeTemplate(
        StorePredictionFieldTemplateRequest $request,
        Team $team,
        PredictionFieldTemplateCatalog $templateCatalog,
        PredictionFieldValidationService $fieldValidator,
    ): RedirectResponse {
        $tournament = Tournament::query()->findOrFail($team->id);

        Gate::authorize('create', [PredictionField::class, $tournament]);

        $payload = $templateCatalog->payloadFor($tournament, $request->validated('template_key'));
        $fieldValidator->validateDefinition($payload);

        $tournament->predictionFields()->create($payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prediction template added.')]);

        return back();
    }

    public function update(UpdatePredictionFieldRequest $request, Team $team, PredictionField $predictionField, PredictionFieldValidationService $fieldValidator): RedirectResponse
    {
        abort_if($predictionField->tournament_id !== $team->id, 404);

        Gate::authorize('update', $predictionField);

        $payload = $request->validated();
        $fieldValidator->validateDefinition($payload);

        $predictionField->update($payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prediction field updated.')]);

        return back();
    }
}
