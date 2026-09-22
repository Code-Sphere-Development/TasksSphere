<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            // Honigtopf: fuer Menschen unsichtbar, Bots fuellen ihn.
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }

    private bool $fromBot = false;

    /**
     * Ein gefuellter Honigtopf gilt nicht als Validierungsfehler - das wuerde
     * dem Bot verraten, woran er gescheitert ist. Der Befund wird vor dem
     * Leeren festgehalten, sonst ueberschreibt die Bereinigung ihr eigenes
     * Signal.
     */
    public function isFromBot(): bool
    {
        return $this->fromBot;
    }

    protected function prepareForValidation(): void
    {
        $this->fromBot = filled($this->input('website'));

        if ($this->fromBot) {
            $this->merge(['website' => '']);
        }
    }
}
