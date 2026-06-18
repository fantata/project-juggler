<?php

namespace App\Livewire;

use App\Enums\DueBucket;
use App\Models\Issue;
use App\Models\Project;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Together extends Component
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public ?string $note = null;

    public ?int $project_id = null;

    #[Validate('required')]
    public string $due_bucket = 'whenever';

    public bool $is_question = false;

    public bool $showAdd = false;

    public function mount(): void
    {
        $this->project_id = $this->sharedProject()->id;
    }

    /**
     * The default home for items added here. Created once if it doesn't exist.
     */
    protected function sharedProject(): Project
    {
        return Project::firstOrCreate(
            ['name' => 'Shared'],
            [
                'type' => 'personal',
                'status' => 'active',
                'money_status' => 'none',
                'last_touched_at' => now(),
            ]
        );
    }

    public function addItem(): void
    {
        $this->validate();

        Issue::create([
            'project_id' => $this->project_id ?: $this->sharedProject()->id,
            'title' => $this->title,
            'description' => $this->note ?: null,
            'status' => 'open',
            'urgency' => 'medium',
            'due_bucket' => $this->due_bucket,
            'is_question' => $this->is_question,
        ]);

        $this->reset('title', 'note', 'is_question', 'showAdd');
        $this->due_bucket = 'whenever';
        $this->project_id = $this->sharedProject()->id;
    }

    public function render()
    {
        $openIssues = Issue::with('project')
            ->whereIn('status', ['open', 'in_progress'])
            ->whereHas('project', fn ($query) => $query->active())
            ->orderByDesc('created_at')
            ->get();

        // Group by due bucket. "Whenever" sits near the end (sortOrder 5) and
        // anything without a bucket falls in last.
        $groups = $openIssues
            ->groupBy(fn (Issue $issue) => $issue->due_bucket?->value ?? '_none')
            ->map(fn ($items, $key) => [
                'key' => $key,
                'label' => $key === '_none' ? 'No timeframe yet' : DueBucket::from($key)->label(),
                'order' => $key === '_none' ? 99 : DueBucket::from($key)->sortOrder(),
                'items' => $items,
            ])
            ->sortBy('order')
            ->values();

        return view('livewire.together', [
            'groups' => $groups,
            'openCount' => $openIssues->count(),
            'projects' => Project::active()->orderBy('name')->get(),
            'dueBuckets' => DueBucket::cases(),
        ]);
    }
}
