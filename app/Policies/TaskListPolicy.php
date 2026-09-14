<?php

namespace App\Policies;

use App\Models\TaskList;
use App\Models\User;

class TaskListPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TaskList $taskList): bool
    {
        return $taskList->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TaskList $taskList): bool
    {
        return $this->view($user, $taskList);
    }

    public function delete(User $user, TaskList $taskList): bool
    {
        return $taskList->user_id === $user->id;
    }
}
