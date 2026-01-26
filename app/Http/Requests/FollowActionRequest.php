<?php

namespace App\Http\Requests;

use App\Models\Action;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FollowActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $actionId = $this->input('action_id');
        $action = Action::find($actionId);
        $coursCloture = $action?->cours_cloture ?? 0;

        return [
            'action_id' => ['required', 'integer', 'exists:actions,id'],

            // Stop loss doit être inférieur au cours_cloture actuel de l'action
            'stop_loss' => [
                'numeric',
                'min:0',
                Rule::when(
                    $this->filled('stop_loss'),
                    ['lt:'.$coursCloture],
                    []
                ),
            ],

            // Take profit doit être supérieur au cours_cloture actuel de l'action
            'take_profit' => [
                'numeric',
                'min:0',
                Rule::when(
                    $this->filled('take_profit'),
                    ['gt:'.$coursCloture],
                    []
                ),
                Rule::when(
                    $this->filled('take_profit') && $this->filled('stop_loss'),
                    ['gt:stop_loss'],
                    []
                ),
            ],
        ];
    }

    public function messages(): array
    {
        $action = Action::find($this->input('action_id'));
        $coursCloture = $action?->cours_cloture ?? 0;

        return [
            'action_id.required' => 'L\'ID de l\'action est requis',
            'action_id.exists' => 'Cette action n\'existe pas',
            'stop_loss.numeric' => 'Le stop loss doit être un nombre',
            'stop_loss.min' => 'Le stop loss doit être positif',
            'stop_loss.lt' => "Le stop loss doit être inférieur au cours de clôture actuel ({$coursCloture})",
            'take_profit.numeric' => 'Le take profit doit être un nombre',
            'take_profit.min' => 'Le take profit doit être positif',
            'take_profit.gt' => "Le take profit doit être supérieur au cours de clôture actuel ({$coursCloture})",
            'take_profit.gt.stop_loss' => 'Le take profit doit être supérieur au stop loss',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => auth()->id(),
        ]);
    }
}
