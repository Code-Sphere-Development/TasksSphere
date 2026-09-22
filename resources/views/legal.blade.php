<x-guest-layout>
    <x-slot name="title">{{ $title }}</x-slot>

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 py-10 px-4">
        <div class="mx-auto max-w-3xl">
            <div class="flex items-center justify-between">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                    <x-application-mark class="h-8 w-8" />
                    TasksSphere
                </a>
                <nav class="flex gap-4 text-sm">
                    <a href="{{ route('legal.imprint') }}" @class(['font-semibold text-gray-900 dark:text-white' => request()->routeIs('legal.imprint'), 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => ! request()->routeIs('legal.imprint')])>{{ __('Impressum') }}</a>
                    <a href="{{ route('legal.privacy') }}" @class(['font-semibold text-gray-900 dark:text-white' => request()->routeIs('legal.privacy'), 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => ! request()->routeIs('legal.privacy')])>{{ __('Datenschutz') }}</a>
                </nav>
            </div>

            <article class="mt-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-10 prose prose-gray dark:prose-invert max-w-none">
                {!! $content !!}
            </article>
        </div>
    </div>
</x-guest-layout>
