<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskListApiController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $teamId = $user->currentTeam?->id;
        return TaskList::where(function ($q) use ($user, $teamId) {
            $q->where('user_id', $user->id);
            if ($teamId) { $q->orWhere('team_id', $teamId); }
        })->orderBy('position')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:tasks,checklist',
            'icon' => 'nullable|string|max:32',
            'color' => 'nullable|string|max:7',
            'team_id' => 'nullable|integer',
        ]);
        if (isset($validated['team_id'])) {
            $team = \App\Models\Team::findOrFail($validated['team_id']);
            abort_unless(Auth::user()->belongsToTeam($team), 403);
            $validated['user_id'] = null;
        } else {
            $validated['user_id'] = Auth::id();
            $validated['team_id'] = null;
        }
        return TaskList::create($validated);
    }

    public function show(TaskList $taskList)
    {
        $this->authorize('view', $taskList);
        $taskList->load($taskList->isChecklist() ? 'items' : 'tasks');
        return $taskList;
    }

    public function update(Request $request, TaskList $taskList)
    {
        $this->authorize('update', $taskList);
        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:32',
            'color' => 'nullable|string|max:7',
            'position' => 'nullable|integer|min:0',
        ]);
        $taskList->update($validated);
        return $taskList;
    }

    public function destroy(TaskList $taskList)
    {
        $this->authorize('delete', $taskList);
        $taskList->delete();
        return response()->json(['message' => 'List deleted']);
    }
}
