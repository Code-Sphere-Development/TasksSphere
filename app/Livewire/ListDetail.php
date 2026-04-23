<?php

namespace App\Livewire;

use App\Models\ListItem;
use App\Models\TaskList;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ListDetail extends Component
{
    public TaskList $taskList;
    public $newItemTitle = '';
    public $editingItemId = null;
    public $editingItemTitle = '';
    public $editingItemNote = '';
    public $showCompletedItems = false;
    public $showTaskPicker = false;

    public function mount(TaskList $taskList): void
    {
        $this->authorize('view', $taskList);
        $this->taskList = $taskList;
    }

    public function render()
    {
        if ($this->taskList->isChecklist()) {
            $activeItems = $this->taskList->items()->where('is_completed', false)->orderBy('position')->get();
            $completedItems = $this->taskList->items()->where('is_completed', true)->orderBy('updated_at', 'desc')->get();
            return view('livewire.list-detail', [
                'activeItems' => $activeItems,
                'completedItems' => $completedItems,
            ])->layout('layouts.app');
        }

        $assignedTasks = $this->taskList->tasks()->where('is_archived', false)->orderBy('due_at')->get();
        $availableTasks = Auth::user()->tasks()->whereNull('task_list_id')->where('is_archived', false)->whereNull('completed_at')->orderBy('title')->get();
        return view('livewire.list-detail', [
            'assignedTasks' => $assignedTasks,
            'availableTasks' => $availableTasks,
        ])->layout('layouts.app');
    }

    // Checklist methods
    public function addItem(): void
    {
        $this->validate(['newItemTitle' => 'required|string|max:255']);
        $this->authorize('update', $this->taskList);
        $maxPosition = $this->taskList->items()->max('position') ?? -1;
        $this->taskList->items()->create(['title' => $this->newItemTitle, 'position' => $maxPosition + 1]);
        $this->newItemTitle = '';
    }

    public function toggleItem(int $itemId): void
    {
        $item = ListItem::where('task_list_id', $this->taskList->id)->findOrFail($itemId);
        $this->authorize('update', $this->taskList);
        $item->update(['is_completed' => !$item->is_completed]);
    }

    public function startEditItem(int $itemId): void
    {
        $item = ListItem::where('task_list_id', $this->taskList->id)->findOrFail($itemId);
        $this->editingItemId = $itemId;
        $this->editingItemTitle = $item->title;
        $this->editingItemNote = $item->note ?? '';
    }

    public function saveEditItem(): void
    {
        $this->validate(['editingItemTitle' => 'required|string|max:255', 'editingItemNote' => 'nullable|string']);
        $item = ListItem::where('task_list_id', $this->taskList->id)->findOrFail($this->editingItemId);
        $this->authorize('update', $this->taskList);
        $item->update(['title' => $this->editingItemTitle, 'note' => $this->editingItemNote ?: null]);
        $this->cancelEditItem();
    }

    public function cancelEditItem(): void
    {
        $this->reset(['editingItemId', 'editingItemTitle', 'editingItemNote']);
    }

    public function deleteItem(int $itemId): void
    {
        $item = ListItem::where('task_list_id', $this->taskList->id)->findOrFail($itemId);
        $this->authorize('update', $this->taskList);
        $item->delete();
    }

    public function clearCompleted(): void
    {
        $this->authorize('update', $this->taskList);
        $this->taskList->items()->where('is_completed', true)->delete();
    }

    // Task list methods
    public function assignTask(int $taskId): void
    {
        $this->authorize('update', $this->taskList);
        $task = Auth::user()->tasks()->findOrFail($taskId);
        $task->update(['task_list_id' => $this->taskList->id]);
        $this->showTaskPicker = false;
    }

    public function removeTask(int $taskId): void
    {
        $this->authorize('update', $this->taskList);
        $task = Auth::user()->tasks()->findOrFail($taskId);
        $task->update(['task_list_id' => null]);
    }
}
