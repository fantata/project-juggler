<?php

namespace App\Livewire;

use App\Mail\QuestionAsked;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithFileUploads;

class Board extends Component
{
    use WithFileUploads;

    public Project $project;

    /** The card whose detail modal is open, if any. */
    public ?int $openCardId = null;

    /** Pending uploads bound to the dropzone on the open card. */
    public array $files = [];

    /** Transient confirmation shown in the open card (e.g. after asking). */
    public ?string $flash = null;

    /** Comment composer on the open card. */
    public string $commentBody = '';

    /** The comment being replied to, if any. */
    public ?int $replyToComment = null;

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

    /**
     * Assign a card to a user, or pass null to clear it. Only touches cards on
     * this project, and only allows real users.
     */
    public function assignCard(int $issueId, ?int $userId): void
    {
        if ($userId !== null && ! User::whereKey($userId)->exists()) {
            return;
        }

        $issue = $this->project->issues()->findOrFail($issueId);
        $issue->update(['assignee_id' => $userId]);
        $this->project->update(['last_touched_at' => now()]);
    }

    public function openCard(int $issueId): void
    {
        // Only open cards that belong to this project.
        $this->openCardId = $this->project->issues()->whereKey($issueId)->value('id');
        $this->reset('files', 'flash', 'commentBody', 'replyToComment');
    }

    public function closeCard(): void
    {
        $this->reset('openCardId', 'files', 'flash', 'commentBody', 'replyToComment');
    }

    public function startCommentReply(int $commentId): void
    {
        $this->replyToComment = $this->openCardComments()->whereKey($commentId)->value('id');
    }

    public function cancelCommentReply(): void
    {
        $this->replyToComment = null;
    }

    public function addComment(): void
    {
        if ($this->openCardId === null) {
            return;
        }

        $this->commentBody = trim($this->commentBody);
        $this->validate(['commentBody' => 'required|string|max:2000']);

        $issue = $this->project->issues()->findOrFail($this->openCardId);

        // A reply only counts if its parent is a comment on this same card.
        $parentId = $this->replyToComment === null
            ? null
            : $issue->comments()->whereKey($this->replyToComment)->value('id');

        $issue->comments()->create([
            'user_id' => auth()->id(),
            'parent_id' => $parentId,
            'body' => $this->commentBody,
        ]);

        $this->reset('commentBody', 'replyToComment');
        $this->project->update(['last_touched_at' => now()]);
    }

    public function deleteComment(int $commentId): void
    {
        // Only your own comments, and only on this project's cards.
        $comment = $this->openCardComments()
            ->where('id', $commentId)
            ->where('user_id', auth()->id())
            ->first();

        $comment?->delete();
    }

    /** Comments across this project's cards — the scope every comment action checks. */
    private function openCardComments()
    {
        return Comment::where('commentable_type', Issue::class)
            ->whereIn('commentable_id', $this->project->issues()->select('id'));
    }

    /**
     * Email the assignee a yes/no question with one-click signed answer links.
     * Needs a question card with someone to ask.
     */
    public function askQuestion(int $issueId): void
    {
        $issue = $this->project->issues()->with('assignee')->findOrFail($issueId);

        if (! $issue->is_question || $issue->assignee === null) {
            return;
        }

        Mail::to($issue->assignee->email)->send(new QuestionAsked($issue));

        $this->flash = "Asked {$issue->assignee->name} — they'll get an email with one-click yes/no.";
    }

    /** Flip a card to/from a yes/no question. */
    public function toggleQuestion(int $issueId): void
    {
        $issue = $this->project->issues()->findOrFail($issueId);
        $issue->update(['is_question' => ! $issue->is_question]);
        $this->flash = null;
    }

    /**
     * Store dropped/chosen files onto the open card. Runs automatically when
     * the dropzone receives files (Livewire updated hook).
     */
    public function updatedFiles(): void
    {
        if ($this->openCardId === null) {
            return;
        }

        $this->validate([
            'files' => 'array|max:10',
            // Allowlist safe types only. No SVG/HTML/scripts: served from our
            // own origin they could execute JS in the session (stored XSS).
            // Posters, audio and PDFs cover what actually lands on a card.
            'files.*' => 'file|max:25600|mimes:jpg,jpeg,png,gif,webp,heic,pdf,'
                .'mp3,wav,m4a,aac,ogg,mp4,mov,webm,txt,csv,doc,docx,xls,xlsx,ppt,pptx,zip',
        ], [
            'files.*.mimes' => "That file type isn't supported — try an image, PDF, audio, video or office doc.",
            'files.*.max' => 'Files need to be under 25 MB each.',
        ]);

        $issue = $this->project->issues()->findOrFail($this->openCardId);

        foreach ($this->files as $file) {
            // Private disk — never web-served directly. Reached only through the
            // auth-gated AttachmentController, which sets safe response headers.
            $path = $file->store("attachments/{$issue->id}", 'local');

            $issue->attachments()->create([
                'user_id' => auth()->id(),
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $this->files = [];
        $this->project->update(['last_touched_at' => now()]);
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $attachment = Attachment::where('id', $attachmentId)
            ->where('attachable_type', Issue::class)
            ->whereIn('attachable_id', $this->project->issues()->select('id'))
            ->first();

        $attachment?->delete();
    }

    public function render()
    {
        $issues = Issue::where('project_id', $this->project->id)
            ->with('assignee')
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($q) => $q->where('is_complete', true),
                'attachments',
            ])
            ->orderBy('position')
            ->orderBy('created_at')
            ->get();

        // Group by column, treating a missing column as the first working list.
        $cards = $issues->groupBy(fn (Issue $i) => $i->board_column ?: 'todo');

        $openCard = $this->openCardId === null ? null : Issue::with([
            'assignee',
            'attachments.uploader',
            'comments' => fn ($q) => $q->whereNull('parent_id')
                ->with(['user', 'replies' => fn ($r) => $r->with('user')->oldest()])
                ->oldest(),
        ])
            ->where('project_id', $this->project->id)
            ->find($this->openCardId);

        return view('livewire.board', [
            'columns' => self::COLUMNS,
            'cards' => $cards,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'openCard' => $openCard,
        ]);
    }
}
