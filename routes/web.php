<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\LegalController;
use App\Livewire\HouseholdManager;
use App\Livewire\ListDetail;
use App\Livewire\ListManager;
use App\Livewire\TaskCalendar;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/impressum', [LegalController::class, 'imprint'])->name('legal.imprint');
Route::get('/datenschutz', [LegalController::class, 'privacy'])->name('legal.privacy');

Route::get('/kontakt', [ContactController::class, 'show'])->name('contact.show');
// Fuenf Nachrichten je zehn Minuten und Adresse reichen fuer Menschen und
// bremsen Bots, die den Honigtopf umgehen.
Route::post('/kontakt', [ContactController::class, 'send'])
    ->middleware('throttle:5,10')
    ->name('contact.send');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/calendar', TaskCalendar::class)->name('calendar.index');

    Route::get('/household', HouseholdManager::class)->name('household.index');

    Route::get('/lists', ListManager::class)->name('lists.index');
    Route::get('/lists/{taskList}', ListDetail::class)->name('lists.show');
});
