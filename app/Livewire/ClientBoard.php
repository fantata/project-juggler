<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The client-facing board behind a project's share link. No login: the token in
 * the URL is the only auth, and every visitor is a "guest" identified by a
 * random per-browser key (cookie) plus a display name they type once.
 *
 * Everything a guest can touch is scoped to THIS project's issues at query time,
 * and only ever renders the client-safe fields (title, description, tasks,
 * comments, files) — never the project's money, notes or AI context.
 */
#[Layout('layouts.public')]
class ClientBoard extends Component
{
    use WithFileUploads;

    public Project $project;

    /** Raw share token from the URL — used to build attachment links. */
    public string $token = '';

    /** Stable per-browser identity (cookie). Lets a guest edit their own stuff. */
    public string $guestKey = '';

    /** The name a guest typed. Empty until they introduce themselves. */
    public string $guestName = '';

    /** Name-entry field (the "Who's this?" prompt). */
    public string $nameInput = '';

    /** Show the name form again so a guest can correct who they are. */
    public bool $editingName = false;

    /** Filters — sensible defaults: everything, newest first. */
    public string $statusFilter = 'all';
    public string $search = '';

    /** Add-a-card composer. */
    public bool $showAddCard = false;
    public string $newTitle = '';
    public string $newDescription = '';

    /** The open card's detail sheet, if any. */
    public ?int $openCardId = null;

    /** Comment composer + pending uploads on the open card. */
    public string $commentBody = '';
    public array $files = [];

    /** Content types accepted on a card — validated by real MIME, not extension,
     *  so browser-recorded voice memos (audio/webm, audio/mp4) pass too. No
     *  SVG/HTML: served same-origin they could run JS (stored XSS). */
    public const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif',
        'application/pdf',
        'audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/mp4', 'audio/x-m4a', 'audio/aac',
        'audio/ogg', 'audio/webm', 'audio/opus',
        'video/mp4', 'video/quicktime', 'video/webm',
        'text/plain', 'text/csv',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip',
    ];

    public function mount(string $token): void
    {
        $this->project = Project::where('share_token', $token)
            ->where('share_enabled', true)
            ->firstOrFail();

        $this->token = $token;

        // Restore identity from cookies; mint a stable key on first visit.
        $this->guestKey = (string) (request()->cookie('jclient_key') ?: '');
        $this->guestName = (string) (request()->cookie('jclient_name') ?: '');

        if ($this->guestKey === '') {
            $this->guestKey = Str::random(40);
            Cookie::queue('jclient_key', $this->guestKey, 60 * 24 * 365);
        }
    }

    /** True once the guest has told us who they are. */
    public function isNamed(): bool
    {
        return trim($this->guestName) !== '';
    }

    public function saveName(): void
    {
        $this->validate(['nameInput' => 'required|string|max:60']);

        $this->guestName = trim($this->nameInput);
        $this->nameInput = '';
        $this->editingName = false;

        Cookie::queue('jclient_name', $this->guestName, 60 * 24 * 365);
    }

    public function addCard(): void
    {
        if (! $this->isNamed()) {
            return;
        }

        $this->validate([
            'newTitle' => 'required|string|max:255',
            'newDescription' => 'nullable|string|max:5000',
        ]);

        $this->project->issues()->create([
            'title' => trim($this->newTitle),
            'description' => trim($this->newDescription) ?: null,
            'status' => 'open',
            'urgency' => 'medium',
            'board_column' => 'ideas',
            // Cards raised by the client belong on the client board.
            'is_client_visible' => true,
            'guest_key' => $this->guestKey,
            'guest_name' => $this->guestName,
        ]);

        $this->reset('newTitle', 'newDescription', 'showAddCard');
        $this->project->update(['last_touched_at' => now()]);
    }

    public function openCard(int $issueId): void
    {
        $this->openCardId = $this->project->issues()->clientVisible()->whereKey($issueId)->value('id');
        $this->reset('commentBody', 'files');
    }

    public function closeCard(): void
    {
        $this->reset('openCardId', 'commentBody', 'files');
    }

    public function addComment(): void
    {
        if (! $this->isNamed() || $this->openCardId === null) {
            return;
        }

        $this->commentBody = trim($this->commentBody);
        $this->validate(['commentBody' => 'required|string|max:2000']);

        $issue = $this->project->issues()->clientVisible()->findOrFail($this->openCardId);

        $issue->comments()->create([
            'body' => $this->commentBody,
            'guest_key' => $this->guestKey,
            'guest_name' => $this->guestName,
        ]);

        $this->reset('commentBody');
        $this->project->update(['last_touched_at' => now()]);
    }

    /** Delete a comment, but only one this same guest wrote. */
    public function deleteOwnComment(int $commentId): void
    {
        $this->guestScopedComments()
            ->whereKey($commentId)
            ->first()?->delete();
    }

    /** Runs when files are dropped/recorded onto the open card. */
    public function updatedFiles(): void
    {
        if (! $this->isNamed() || $this->openCardId === null) {
            return;
        }

        $this->validate([
            'files' => 'array|max:10',
            'files.*' => ['file', 'max:25600', 'mimetypes:'.implode(',', self::ALLOWED_MIMES)],
        ], [
            'files.*.mimetypes' => "That file type isn't supported — try an image, PDF, audio, video or office doc.",
            'files.*.max' => 'Files need to be under 25 MB each.',
        ]);

        $issue = $this->project->issues()->clientVisible()->findOrFail($this->openCardId);

        foreach ($this->files as $file) {
            // Private disk — reachable only through the token-scoped controller.
            $path = $file->store("attachments/{$issue->id}", 'local');

            $issue->attachments()->create([
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'guest_key' => $this->guestKey,
                'guest_name' => $this->guestName,
            ]);
        }

        $this->files = [];
        $this->project->update(['last_touched_at' => now()]);
    }

    /** Remove a file, but only one this same guest uploaded. */
    public function deleteOwnAttachment(int $attachmentId): void
    {
        Attachment::where('attachable_type', Issue::class)
            ->whereIn('attachable_id', $this->project->issues()->clientVisible()->select('id'))
            ->where('guest_key', $this->guestKey)
            ->whereKey($attachmentId)
            ->first()?->delete();
    }

    /** Comments across this project's cards authored by the current guest. */
    private function guestScopedComments()
    {
        return Comment::where('commentable_type', Issue::class)
            ->whereIn('commentable_id', $this->project->issues()->clientVisible()->select('id'))
            ->where('guest_key', $this->guestKey);
    }

    public function render()
    {
        $search = trim($this->search);

        $issues = $this->project->issues()
            ->clientVisible()
            ->withCount([
                'comments',
                'attachments',
                'tasks',
                'tasks as completed_tasks_count' => fn ($q) => $q->where('is_complete', true),
            ])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")
            ->get();

        $openCard = $this->openCardId === null ? null : Issue::with([
            'tasks',
            'attachments',
            'comments' => fn ($q) => $q->with('user')->oldest(),
        ])
            ->where('project_id', $this->project->id)
            ->find($this->openCardId);

        return view('livewire.client-board', [
            'issues' => $issues,
            'openCard' => $openCard,
        ]);
    }
}
