<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Loescht das TasksSphere-Konto wie zuvor Jetstreams DeleteUser. Das
 * CodeSphere-Konto in AccountSphere bleibt bestehen.
 *
 * Ohne lokales Passwort bestaetigt der Nutzer durch Eingabe seiner E-Mail.
 */
class DeleteAccountForm extends Component
{
    public bool $confirmingUserDeletion = false;

    public string $confirmation = '';

    public function confirmUserDeletion(): void
    {
        $this->resetErrorBag();

        $this->confirmation = '';

        $this->confirmingUserDeletion = true;
    }

    public function deleteUser()
    {
        $user = Auth::user();

        if (mb_strtolower(trim($this->confirmation)) !== mb_strtolower((string) $user->email)) {
            $this->addError('confirmation', 'Bitte gib zur Bestätigung deine E-Mail-Adresse ein.');

            return null;
        }

        $user->tokens()->delete();
        $user->delete();

        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    }

    public function render()
    {
        return view('livewire.delete-account-form');
    }
}
