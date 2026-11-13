@props(['id'])

{{-- Die ID, unter der Aufgaben in der API und von Agenten angesprochen werden. --}}
<span {{ $attributes->merge(['class' => 'tabular-nums text-gray-400 dark:text-gray-500']) }} title="{{ __('Aufgaben-ID') }}">#{{ $id }}</span>
