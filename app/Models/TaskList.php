<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskList extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'team_id', 'title', 'description', 'type', 'icon', 'color', 'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ListItem::class)->orderBy('position');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId)->whereNull('team_id');
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function isChecklist(): bool
    {
        return $this->type === 'checklist';
    }

    public function isTaskList(): bool
    {
        return $this->type === 'tasks';
    }

    public function itemCount(): int
    {
        if ($this->isChecklist()) {
            return $this->items()->count();
        }

        return $this->tasks()->count();
    }

    public function completedCount(): int
    {
        if ($this->isChecklist()) {
            return $this->items()->where('is_completed', true)->count();
        }

        return $this->tasks()->whereNotNull('completed_at')->count();
    }
}
