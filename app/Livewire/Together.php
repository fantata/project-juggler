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

    /**
     * Issues this screen is allowed to act on — those in active projects (exactly
     * what render() surfaces). This app has no per-user project membership; both
     * users see every active project by design (as with the board/messenger), so
     * "actionable" is bounded to the active set rather than to a single user.
     */
    private function actionableIssue(int $issueId): ?Issue
    {
        return Issue::whereKey($issueId)
            ->whereHas('project', fn ($query) => $query->active())
            ->first();
    }

    /** Mark an item done — it drops off the open list. */
    public function complete(int $issueId): void
    {
        $this->actionableIssue($issueId)?->update(['status' => 'done']);
    }

    /** Answer a question (yes/no or free text) — it leaves the questions list. */
    public function answer(int $issueId, string $value): void
    {
        $issue = $this->actionableIssue($issueId);

        if ($issue && $issue->is_question) {
            $issue->update(['answer' => $value, 'answered_at' => now()]);
        }
    }

    /** Persist a hand-dragged priority order. $orderedIds is the radar list, top first. */
    public function reorder(array $orderedIds): void
    {
        $allowed = Issue::whereIn('id', $orderedIds)
            ->whereHas('project', fn ($query) => $query->active())
            ->pluck('id')
            ->all();

        foreach (array_values($orderedIds) as $position => $id) {
            if (in_array((int) $id, $allowed, true)) {
                Issue::query()->whereKey((int) $id)->update(['position' => $position]);
            }
        }
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

        // Questions live only in the questions section; once answered they're
        // resolved and drop off Together entirely (the answer is recorded).
        $rest = $open->reject(fn (Issue $i) => $i->is_question);

        // 2. On the radar: anything shared here, or given a real timeframe — one flat
        //    list in hand-dragged priority order (position), newest first as tie-break.
        $radar = $rest
            ->filter(fn (Issue $i) => $i->project_id === $shared->id
                || in_array($i->due_bucket?->value, self::SOON, true))
            ->sortBy('position')
            ->values();

        // 3. Everything else — quiet project tasks, no action needed. Collapsed.
        $other = $rest
            ->reject(fn (Issue $i) => $radar->contains('id', $i->id))
            ->groupBy(fn (Issue $i) => $i->project->name)
            ->sortKeys();

        return view('livewire.together', [
            'questions' => $questions,
            'radar' => $radar,
            'soon' => self::SOON,
            'otherByProject' => $other,
            'otherCount' => $other->flatten()->count(),
            'projects' => Project::active()->orderBy('name')->get(),
            'dueBuckets' => DueBucket::cases(),
        ]);
    }
}
