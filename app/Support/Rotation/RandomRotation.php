<?php

namespace App\Support\Rotation;

use App\Models\Task;
use App\Models\User;

class RandomRotation implements RotationStrategy
{
    public function next(Task $task, $candidates): ?User
    {
        return $candidates->random();
    }
}
