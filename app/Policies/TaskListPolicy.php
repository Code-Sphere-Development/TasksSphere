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
        if ($taskList->user_id === $user->id) {
            return true;
        }

        if ($taskList->team_id && $user->belongsToTeam(\App\Models\Team::find($taskList->team_id))) {
            return true;
        }

        return false;
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
        if ($taskList->user_id === $user->id) {
            return true;
        }

        if ($taskList->team_id) {
            $team = \App\Models\Team::find($taskList->team_id);
            return $team && $user->ownsTeam($team);
        }

        return false;
    }
}
