<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListItem;
use App\Models\TaskList;
use Illuminate\Http\Request;

class ListItemApiController extends Controller
{
    public function index(TaskList $taskList)
    {
        $this->authorize('view', $taskList);
        return $taskList->items()->orderBy('position')->get();
    }

    public function store(Request $request, TaskList $taskList)
    {
        $this->authorize('update', $taskList);
        abort_unless($taskList->isChecklist(), 422, 'Items can only be added to checklists.');
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'note' => 'nullable|string',
        ]);
        $maxPosition = $taskList->items()->max('position') ?? -1;
        $validated['position'] = $maxPosition + 1;
        return $taskList->items()->create($validated);
    }

    public function update(Request $request, TaskList $taskList, ListItem $item)
    {
        $this->authorize('update', $taskList);
        abort_unless($item->task_list_id === $taskList->id, 404);
        $validated = $request->validate([
            'title' => 'string|max:255',
            'note' => 'nullable|string',
            'is_completed' => 'boolean',
            'position' => 'integer|min:0',
        ]);
        $item->update($validated);
        return $item;
    }

    public function destroy(TaskList $taskList, ListItem $item)
    {
        $this->authorize('update', $taskList);
        abort_unless($item->task_list_id === $taskList->id, 404);
        $item->delete();
        return response()->json(['message' => 'Item deleted']);
    }
}
