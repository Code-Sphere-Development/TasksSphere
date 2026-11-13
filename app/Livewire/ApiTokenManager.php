<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Ersatz fuer Jetstreams Token-Verwaltung. Tokens tragen volle Rechte - die
 * Einschraenkung per Abilities ist bewusst verworfen (Gehirn-Agent).
 */
class ApiTokenManager extends Component
{
    public string $name = '';

    public bool $displayingToken = false;

    public ?string $plainTextToken = null;

    public bool $confirmingApiTokenDeletion = false;

    public ?int $apiTokenIdBeingDeleted = null;

    public function createApiToken(): void
    {
        $this->resetErrorBag();

        $this->validate(['name' => ['required', 'string', 'max:255']]);

        $this->displayTokenValue(Auth::user()->createToken($this->name)->plainTextToken);

        $this->name = '';

        $this->dispatch('created');
    }

    public function confirmApiTokenDeletion(int $tokenId): void
    {
        $this->confirmingApiTokenDeletion = true;

        $this->apiTokenIdBeingDeleted = $tokenId;
    }

    public function deleteApiToken(): void
    {
        Auth::user()->tokens()->whereKey($this->apiTokenIdBeingDeleted)->delete();

        $this->confirmingApiTokenDeletion = false;

        $this->apiTokenIdBeingDeleted = null;
    }

    public function render()
    {
        return view('livewire.api-token-manager', [
            'tokens' => Auth::user()->tokens()->orderBy('name')->get(),
        ]);
    }

    private function displayTokenValue(string $plainTextToken): void
    {
        // Sanctum liefert "id|token"; Jetstream zeigte nur den Token-Teil.
        $this->plainTextToken = explode('|', $plainTextToken, 2)[1];

        $this->displayingToken = true;

        $this->dispatch('showing-token-modal');
    }
}
