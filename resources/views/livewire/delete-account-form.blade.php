<x-action-section>
    <x-slot name="title">
        {{ __('Delete Account') }}
    </x-slot>

    <x-slot name="description">
        Löscht dein Konto in TasksSphere. Dein CodeSphere-Konto bleibt bestehen.
    </x-slot>

    <x-slot name="content">
        <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
            Dein Konto und die zugehörigen Daten in TasksSphere werden endgültig gelöscht.
        </div>

        <div class="mt-5">
            <x-danger-button wire:click="confirmUserDeletion" wire:loading.attr="disabled">
                {{ __('Delete Account') }}
            </x-danger-button>
        </div>

        <x-dialog-modal wire:model.live="confirmingUserDeletion">
            <x-slot name="title">
                {{ __('Delete Account') }}
            </x-slot>

            <x-slot name="content">
                Bist du sicher? Gib zur Bestätigung deine E-Mail-Adresse ein.

                <div class="mt-4">
                    <x-input type="email" class="mt-1 block w-3/4"
                             placeholder="{{ Auth::user()->email }}"
                             wire:model="confirmation"
                             wire:keydown.enter="deleteUser" />

                    <x-input-error for="confirmation" class="mt-2" />
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$toggle('confirmingUserDeletion')" wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3" wire:click="deleteUser" wire:loading.attr="disabled">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </x-slot>
        </x-dialog-modal>
    </x-slot>
</x-action-section>
