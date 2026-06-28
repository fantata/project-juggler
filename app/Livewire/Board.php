<?php

namespace App\Livewire;

use App\Models\Issue;
use App\Models\Project;
use Livewire\Component;

class Board extends Component
{
    public Project $project;

    /**
     * Fixed kanban columns for v1: key => label. Customisable columns can come
     * later; two people running two shows don't need that yet.
     */
    public const COLUMNS = [
        'ideas' => 'Ideas',
        'todo' => 'To do',
        'doing' => 'Doing',
        'done' => 'Done',
    ];

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Move a card to a column. Used by the mobile move buttons now and by
     * drag-and-drop (SortableJS) next. Only touches cards on this project.
     */
    public function moveCard(int $issueId, string $column): void
    {
        if (! array_key_exists($column, self::COLUMNS)) {
            return;
        }

        $issue = $this->project->issues()->findOrFail($issueId);
        $issue->update(['board_column' => $column]);
        $this->project->update(['last_touched_at' => now()]);
    }

    public function render()
    {
        $issues = Issue::where('project_id', $this->project->id)
            ->with('assignee')
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($q) => $q->where('is_complete', true),
            ])
            ->orderBy('position')
            ->orderBy('created_at')
            ->get();

        // Group by column, treating a missing column as the first working list.
        $cards = $issues->groupBy(fn (Issue $i) => $i->board_column ?: 'todo');

        return view('livewire.board', [
            'columns' => self::COLUMNS,
            'cards' => $cards,
        ]);
    }
}
