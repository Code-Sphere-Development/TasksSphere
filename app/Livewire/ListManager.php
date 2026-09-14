<?php

namespace App\Livewire;

use App\Models\TaskList;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ListManager extends Component
{
    public $title = '';

    public $description = '';

    public $type = 'checklist';

    public $icon = '';

    public $color = '';

    public $showForm = false;

    public $isEditing = false;

    public $editingListId = null;

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'type' => 'required|in:tasks,checklist',
        'icon' => 'nullable|string|max:32',
        'color' => 'nullable|string|max:7',
    ];

    public function render()
    {
        $myLists = TaskList::forUser(Auth::id())->orderBy('position')->get();

        return view('livewire.list-manager', [
            'myLists' => $myLists,
        ])->layout('layouts.app');
    }

    public function showCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function createList(): void
    {
        $this->validate();
        TaskList::create([
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'icon' => $this->icon ?: null,
            'color' => $this->color ?: null,
            'user_id' => Auth::id(),
        ]);
        $this->resetForm();
    }

    public function editList(int $id): void
    {
        $list = TaskList::findOrFail($id);
        $this->authorize('update', $list);
        $this->editingListId = $id;
        $this->title = $list->title;
        $this->description = $list->description;
        $this->type = $list->type;
        $this->icon = $list->icon ?? '';
        $this->color = $list->color ?? '';
        $this->isEditing = true;
        $this->showForm = true;
    }

    public function updateList(): void
    {
        $this->validate();
        $list = TaskList::findOrFail($this->editingListId);
        $this->authorize('update', $list);
        $list->update([
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'icon' => $this->icon ?: null,
            'color' => $this->color ?: null,
        ]);
        $this->resetForm();
    }

    public function deleteList(int $id): void
    {
        $list = TaskList::findOrFail($id);
        $this->authorize('delete', $list);
        $list->delete();
    }

    public function resetForm(): void
    {
        $this->reset(['title', 'description', 'type', 'icon', 'color', 'showForm', 'isEditing', 'editingListId']);
    }
}
