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

    /** Timeframes that mean "this wants attention soon" (whenever does not). */
    private const SOON = ['today', 'tomorrow', 'this_week', 'next_week'];

    public function mount(): void
    {
        $this->project_id = $this->sharedProject()->id;
    }

    /** The default home for items added here. Created once if it doesn't exist. */
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
        $shared = $this->sharedProject();

        $open = Issue::with('project')
            ->whereIn('status', ['open', 'in_progress'])
            ->whereHas('project', fn ($query) => $query->active())
            ->orderByDesc('created_at')
            ->get();

        // 1. Questions waiting on an answer — the things that genuinely need a person.
        $questions = $open
            ->filter(fn (Issue $i) => $i->is_question && $i->answer === null)
            ->values();

        $rest = $open->reject(fn (Issue $i) => $i->is_question && $i->answer === null);

        // 2. On the radar: anything shared here, or given a real timeframe.
        $onRadar = $rest->filter(fn (Issue $i) => $i->project_id === $shared->id
            || in_array($i->due_bucket?->value, self::SOON, true));

        $radarGroups = $onRadar
            ->groupBy(fn (Issue $i) => in_array($i->due_bucket?->value, self::SOON, true)
                ? $i->due_bucket->value
                : 'shared')
            ->map(fn ($items, $key) => [
                'key' => $key,
                'label' => $key === 'shared' ? 'Shared' : DueBucket::from($key)->label(),
                'order' => $key === 'shared' ? 9 : DueBucket::from($key)->sortOrder(),
                'items' => $items,
            ])
            ->sortBy('order')
            ->values();

        // 3. Everything else — quiet project tasks, no action needed. Collapsed.
        $other = $rest
            ->reject(fn (Issue $i) => $onRadar->contains('id', $i->id))
            ->groupBy(fn (Issue $i) => $i->project->name)
            ->sortKeys();

        return view('livewire.together', [
            'questions' => $questions,
            'radarGroups' => $radarGroups,
            'otherByProject' => $other,
            'otherCount' => $other->flatten()->count(),
            'projects' => Project::active()->orderBy('name')->get(),
            'dueBuckets' => DueBucket::cases(),
        ]);
    }
}
