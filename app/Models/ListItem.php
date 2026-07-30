<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_list_id', 'title', 'note', 'is_completed', 'position',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'position' => 'integer',
    ];

    public function taskList(): BelongsTo
    {
        return $this->belongsTo(TaskList::class);
    }
}
