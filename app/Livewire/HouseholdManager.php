<?php

namespace App\Livewire;

use App\Models\Household;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Verwaltung der Haushalte, aus denen sich der Kreis zuweisbarer Personen
 * speist.
 *
 * Mitglieder werden ueber ihre E-Mail-Adresse gefunden, nicht eingeladen:
 * TasksSphere hat keine Mailinfrastruktur. Wer aufgenommen werden soll,
 * braucht ein Konto.
 */
class HouseholdManager extends Component
{
    public string $name = '';

    public string $inviteEmail = '';

    public function render(): View
    {
        return view('livewire.household-manager', [
            'households' => Auth::user()->households()->with('members', 'owner')->get(),
        ])->layout('layouts.app');
    }

    public function createHousehold(): void
    {
        $this->validate(['name' => 'required|string|max:255']);

        Household::createFor(Auth::user(), $this->name);

        $this->reset('name');
    }

    public function addMember(int $householdId): void
    {
        $household = $this->ownedHousehold($householdId);

        $this->validate(['inviteEmail' => 'required|email']);

        $user = User::where('email', $this->inviteEmail)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'inviteEmail' => __('Kein Nutzer mit dieser E-Mail-Adresse gefunden.'),
            ]);
        }

        $household->addMember($user);

        $this->reset('inviteEmail');
    }

    public function removeMember(int $householdId, int $userId): void
    {
        $household = $this->ownedHousehold($householdId);

        $household->removeMember(User::findOrFail($userId));
    }

    public function leaveHousehold(int $householdId): void
    {
        $household = Household::findOrFail($householdId);

        abort_unless($household->hasMember(Auth::user()), 403);

        $household->removeMember(Auth::user());
    }

    /**
     * Verwalten darf nur der Besitzer. Livewire-Methoden sind ueber die
     * Leitung aufrufbar, die Pruefung gehoert deshalb hierher und nicht
     * ausschliesslich ins Blade.
     */
    private function ownedHousehold(int $householdId): Household
    {
        $household = Household::findOrFail($householdId);

        abort_unless($household->owner_id === Auth::id(), 403);

        return $household;
    }
}
