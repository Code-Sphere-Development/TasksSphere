<?php

use App\Models\Task;

/*
|--------------------------------------------------------------------------
| Herkunftsverweis im Titel
|--------------------------------------------------------------------------
| Der Gehirn-Agent stellt Titeln einen Verweis voran, etwa
| "FamilyNetwork#1 . Einkaufsliste ergaenzen". In der Liste frisst der Verweis
| den sichtbaren Teil des Titels auf. Er wird deshalb abgetrennt und getrennt
| dargestellt - am gespeicherten Titel aendert sich nichts.
*/

test('a leading reference with a dot separator is split off', function () {
    $task = new Task(['title' => 'FamilyNetwork#1 . Einkaufsliste ergänzen']);

    expect($task->titleReference)->toBe('FamilyNetwork#1');
    expect($task->displayTitle)->toBe('Einkaufsliste ergänzen');
});

test('a dash separator works as well', function () {
    $task = new Task(['title' => 'TasksSphere#42 - Kalender prüfen']);

    expect($task->titleReference)->toBe('TasksSphere#42');
    expect($task->displayTitle)->toBe('Kalender prüfen');
});

test('a colon separator works as well', function () {
    $task = new Task(['title' => 'InvoiceSphere#7: Rechnung stellen']);

    expect($task->titleReference)->toBe('InvoiceSphere#7');
    expect($task->displayTitle)->toBe('Rechnung stellen');
});

test('a reference without a separator works as well', function () {
    $task = new Task(['title' => 'GrandmaRecipes#3 Rezept nachtragen']);

    expect($task->titleReference)->toBe('GrandmaRecipes#3');
    expect($task->displayTitle)->toBe('Rezept nachtragen');
});

test('a plain title keeps everything', function () {
    $task = new Task(['title' => 'Müll rausbringen']);

    expect($task->titleReference)->toBeNull();
    expect($task->displayTitle)->toBe('Müll rausbringen');
});

test('a hash in the middle is not a reference', function () {
    $task = new Task(['title' => 'Thema #5 besprechen']);

    expect($task->titleReference)->toBeNull();
    expect($task->displayTitle)->toBe('Thema #5 besprechen');
});

test('a reference without anything after it keeps the title intact', function () {
    $task = new Task(['title' => 'FamilyNetwork#1']);

    expect($task->titleReference)->toBeNull();
    expect($task->displayTitle)->toBe('FamilyNetwork#1');
});

test('the stored title is never changed', function () {
    $task = new Task(['title' => 'FamilyNetwork#1 . Einkaufsliste ergänzen']);

    expect($task->title)->toBe('FamilyNetwork#1 . Einkaufsliste ergänzen');
});
